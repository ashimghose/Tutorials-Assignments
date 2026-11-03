@props(['photos'])

{{-- Free-licence stock photos (Pexels) in public/images/photos, cropped 4:3 and
     saved as WebP at 640 and 1200 px. They illustrate the kind of class, so
     never caption them as our own students or teachers. --}}
<div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3">
    @foreach ($photos as $photo)
        <img src="/images/photos/{{ $photo['name'] }}-640.webp"
             srcset="/images/photos/{{ $photo['name'] }}-640.webp 640w, /images/photos/{{ $photo['name'] }}-1200.webp 1200w"
             sizes="(min-width: 768px) 33vw, {{ $loop->first ? '100vw' : '50vw' }}"
             alt="{{ $photo['alt'] }}" width="640" height="480" loading="lazy" decoding="async"
             @class(['aspect-[4/3] w-full min-w-0 rounded-2xl object-cover', 'col-span-2 md:col-span-1' => $loop->first])>
    @endforeach
</div>
