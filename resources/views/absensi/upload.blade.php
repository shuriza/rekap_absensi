@extends('layouts.app')

@section('content')
  <div class="max-w-xl mx-auto mt-10 bg-white p-6 shadow rounded">
    <h2 class="text-xl font-bold mb-4">Upload File Excel Absensi</h2>

    @if (session('success'))
      <div class="bg-green-100 text-green-700 p-2 rounded mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('absensi.import') }}" enctype="multipart/form-data">
      @csrf

      <label for="file_excel" class="block mb-2 text-sm font-medium">Upload Beberapa File Excel:</label>
      <input type="file" name="file_excel[]" multiple required
        class="block w-full p-2 border rounded mb-4">

      <button type="submit"
        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Upload</button>
    </form>

  </div>
@endsection
