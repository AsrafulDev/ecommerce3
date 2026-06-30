@extends('errors::minimal')

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('{{ __('message') }}', __('Too Many Requests'))
