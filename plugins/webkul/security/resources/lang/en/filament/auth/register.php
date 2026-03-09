<?php

return [
    'title'   => 'Create an account',
    'heading' => 'Sign up for your company',

    'form' => [
        'company_name'         => 'Company name',
        'name'                 => 'Your name',
        'email'                => 'Email address',
        'password'             => 'Password',
        'password_confirmation' => 'Confirm password',
    ],

    'actions' => [
        'register' => 'Create account',
        'login'     => 'Sign in',
        'before'    => 'Already have an account?',
    ],

    'notifications' => [
        'throttled' => 'Too many sign up attempts. Please try again in :seconds seconds.',
    ],
];
