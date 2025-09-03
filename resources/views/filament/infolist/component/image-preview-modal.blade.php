@props([
    'state',
])

<div class="flex justify-center p-2">
    <img src="{{ \Illuminate\Support\Facades\Storage::url($state) }}"
         alt="Pratinjau Lampiran"
         style="max-width: 100%; height: auto; max-height: 75vh;" />
</div>
