{{-- Global Alpine factory for the S3 multipart uploader. Injected once per panel page. --}}
<script>
    window.s3MultipartUpload = window.s3MultipartUpload || function (config) {
        return {
            state: config.state,
            accept: config.accept || [],
            maxBytes: config.maxBytes,
            csrf: config.csrf,
            urls: config.urls,

            status: 'idle',
            percent: 0,
            fileName: '',
            humanSize: '',
            error: '',

            _abort: null,      // { key, uploadId }
            _cancelled: false,
            _xhr: null,

            init() {
                if (this.state) {
                    this.status = 'done';
                    this.fileName = String(this.state).split('/').pop();
                }
            },

            get hint() {
                const max = this.maxBytes ? `Max ${this.bytes(this.maxBytes)}.` : '';
                const types = this.accept.length ? this.accept.map(t => t.split('/')[1]).join(', ') : '';
                return [max, types].filter(Boolean).join(' ');
            },

            bytes(n) {
                const u = ['B', 'KB', 'MB', 'GB', 'TB'];
                let i = 0;
                while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
                return `${Math.round(n * 10) / 10} ${u[i]}`;
            },

            async post(url, body) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(body),
                });
                if (!res.ok) {
                    let msg = `Request failed (${res.status})`;
                    try { const j = await res.json(); msg = j.message || msg; } catch (e) {}
                    throw new Error(msg);
                }
                return res.json();
            },

            async start(file) {
                if (!file) return;

                if (this.accept.length && !this.accept.includes(file.type)) {
                    this.fail(`File type "${file.type || 'unknown'}" is not allowed.`);
                    return;
                }
                if (this.maxBytes && file.size > this.maxBytes) {
                    this.fail(`File is ${this.bytes(file.size)}; the limit is ${this.bytes(this.maxBytes)}.`);
                    return;
                }

                this._cancelled = false;
                this.error = '';
                this.status = 'uploading';
                this.percent = 0;
                this.fileName = file.name;
                this.humanSize = this.bytes(file.size);

                try {
                    const { key, upload_id: uploadId } = await this.post(this.urls.create, {
                        filename: file.name,
                        content_type: file.type,
                    });
                    this._abort = { key, uploadId };

                    // Keep part count under the 10,000 S3 limit; min part 5 MB, target ~50 MB parts.
                    const minPart = 5 * 1024 * 1024;
                    const partSize = Math.max(minPart, Math.ceil(file.size / 9000), 50 * 1024 * 1024);
                    const total = Math.ceil(file.size / partSize) || 1;

                    const parts = new Array(total);
                    const uploaded = new Array(total).fill(0);
                    let next = 0;

                    const worker = async () => {
                        while (true) {
                            if (this._cancelled) return;
                            const idx = next++;
                            if (idx >= total) return;
                            const partNumber = idx + 1;
                            const blob = file.slice(idx * partSize, Math.min((idx + 1) * partSize, file.size));

                            const { url } = await this.post(this.urls.sign, {
                                key, upload_id: uploadId, part_number: partNumber,
                            });

                            const etag = await this.putPart(url, blob, idx, uploaded, file.size);
                            parts[idx] = { PartNumber: partNumber, ETag: etag };
                        }
                    };

                    const pool = Math.min(4, total);
                    await Promise.all(Array.from({ length: pool }, () => worker()));

                    if (this._cancelled) return;

                    await this.post(this.urls.complete, { key, upload_id: uploadId, parts });

                    this.state = key;
                    this._abort = null;
                    this.percent = 100;
                    this.status = 'done';
                } catch (e) {
                    if (!this._cancelled) {
                        await this.safeAbort();
                        this.fail(e.message || 'Upload failed.');
                    }
                }
            },

            putPart(url, blob, idx, uploaded, totalBytes) {
                // XHR (not fetch) so we get per-part upload progress events.
                return new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('PUT', url, true);
                    xhr.upload.onprogress = (e) => {
                        uploaded[idx] = e.loaded;
                        const sum = uploaded.reduce((a, b) => a + b, 0);
                        this.percent = Math.min(99, Math.floor((sum / totalBytes) * 100));
                    };
                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            const etag = xhr.getResponseHeader('ETag');
                            if (!etag) {
                                reject(new Error('Missing ETag — check the bucket CORS ExposeHeaders config.'));
                            } else {
                                resolve(etag);
                            }
                        } else {
                            reject(new Error(`Part upload failed (${xhr.status}).`));
                        }
                    };
                    xhr.onerror = () => reject(new Error('Network error during part upload.'));
                    this._xhr = xhr;
                    xhr.send(blob);
                });
            },

            async safeAbort() {
                if (!this._abort) return;
                try {
                    await this.post(this.urls.abort, {
                        key: this._abort.key,
                        upload_id: this._abort.uploadId,
                    });
                } catch (e) { /* best effort */ }
                this._abort = null;
            },

            async cancel() {
                this._cancelled = true;
                if (this._xhr) { try { this._xhr.abort(); } catch (e) {} }
                await this.safeAbort();
                this.reset();
            },

            fail(msg) {
                this.error = msg;
                this.status = 'error';
                this.percent = 0;
            },

            reset() {
                this.state = null;
                this.status = 'idle';
                this.percent = 0;
                this.fileName = '';
                this.error = '';
            },
        };
    };
</script>
