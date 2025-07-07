<!-- resources/views/components/dropdown-link.blade.php -->
@props(['active', 'href', 'method' => 'get'])

@php
  $isActive = $active ?? false;
  $classes = $isActive
      ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-indigo-400 text-start text-base font-medium text-indigo-700 bg-indigo-50 focus:outline-none focus:text-indigo-800 focus:bg-indigo-100 focus:border-indigo-700 transition duration-150 ease-in-out'
      : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out';
@endphp

@if (strtolower($method) === 'post')
  <form method="POST" action="{{ $href }}">
    @csrf
    @method('POST')
    <button type="submit" {{ $attributes->merge(['class' => $classes]) }}>
      {{ $slot }}
    </button>
  </form>
@else
  <a {{ $attributes->merge(['href' => $href, 'class' => $classes]) }}>
    {{ $slot }}
  </a>
@endif
