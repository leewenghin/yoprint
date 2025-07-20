<div
    x-data="{ show: true }"
    x-init="setTimeout(() => show = false, 3000)"
    x-show="show"
    x-transition
    class="fixed top-4 right-4 z-50 space-y-3 w-96"
>
    @if (session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded shadow">
        {{ session('success') }}
    </div>
    @endif

    @if (session('failed'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded shadow">
        {{ session('failed') }}
    </div>
    @endif

    @if ($errors->any())
    <ul class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded shadow">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    @endif
</div>
