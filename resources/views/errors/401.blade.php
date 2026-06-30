@extends('errors::minimal')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('{{ __('message') }}', __('Unauthorized'))
