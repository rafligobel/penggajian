@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Pengaturan Tanda Tangan Bendahara</h2>

        <div class="bg-white shadow-md rounded-lg p-6">

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('pengaturan.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="tanda_tangan" class="block text-sm font-medium text-gray-700">
                            Upload Tanda Tangan (PNG, JPG, maks: 1MB)
                        </label>
                        <div class="mt-1">
                            <input id="tanda_tangan" name="tanda_tangan" type="file"
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none"
                                accept=".png,.jpg,.jpeg">
                        </div>
                        @error('tanda_tangan')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($tandaTangan && $tandaTangan->value)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanda Tangan Saat Ini</label>
                            <div>
                                <img src="{{ asset('storage/' . $tandaTangan->value) }}" alt="Tanda Tangan"
                                    class="border border-gray-200 rounded-md inline-block" style="height: 200px;">
                            </div>
                        </div>
                    @endif
                </div>
                <br>
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
