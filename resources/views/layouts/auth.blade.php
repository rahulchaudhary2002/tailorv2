<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') | {{ config('app.name', 'Fashion Tailor Pro') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/auth-styles.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/auth-responsive.css') }}" rel="stylesheet">
    @yield('page-specific-style')
</head>

<body>
    @yield('content')

    <script src="{{ asset('assets/js/auth-common.js') }}"></script>
    @yield('page-specific-script')
</body>

</html>