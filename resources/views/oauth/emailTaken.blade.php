@extends('errors.layout')

@section('title', 'Login Error')

@section('message', 'Email already used in local adapt authentication.  If you are trying to create an account  please either directly log in to Adapt with that email account or use a different email through the single sign on.  Please first visit: <a href="https://sso.libretexts.org/cas/logout">https://sso.libretexts.org/cas/logout</a> in your browser to reset the authentication process.')
