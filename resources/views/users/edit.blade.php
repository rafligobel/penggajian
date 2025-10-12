@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Edit Pengguna</h1>
        </div>

        <div class="section-body">
            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @method('PUT') {{-- Gunakan PUT atau PATCH untuk update --}}

                {{-- Panggil partial form yang sama, variabel $user sudah ada dari controller --}}
                @include('users._form')
            </form>
        </div>
    </section>
@endsection
