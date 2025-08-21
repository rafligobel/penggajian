@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">Kelola Tanda Tangan Bendahara</h2>

        {{-- Container baru untuk membuat layout lebih compact --}}
        <div class="max-w-2xl mx-auto">

            {{-- Notifikasi Sukses --}}
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p class="font-bold">Sukses</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white shadow-md rounded-lg p-6">
                <div class="border-b border-gray-200 pb-4 mb-6">
                    <h3 class="text-lg font-semibold leading-6 text-gray-900">Upload Tanda Tangan</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        File yang diunggah akan digunakan sebagai tanda tangan digital pada setiap slip gaji.
                    </p>
                </div>

                <form action="{{ route('tanda_tangan.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 gap-6">
                        {{-- Form Upload --}}
                        <div>
                            <label for="tanda_tangan" class="block text-sm font-medium text-gray-700 mb-1">
                                File Gambar (PNG, JPG, maks: 1MB)
                            </label>
                            <input id="tanda_tangan" name="tanda_tangan" type="file"
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none"
                                accept=".png,.jpg,.jpeg">
                            @error('tanda_tangan')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Preview Tanda Tangan --}}
                        @if ($tandaTangan && $tandaTangan->value)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanda Tangan Saat Ini</label>
                                <div class="mt-2 p-2 border border-gray-200 rounded-md inline-block">
                                    <img src="{{ asset('storage/' . $tandaTangan->value) }}" alt="Tanda Tangan"
                                        style="height: 80px;">
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Tombol Simpan --}}
                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
