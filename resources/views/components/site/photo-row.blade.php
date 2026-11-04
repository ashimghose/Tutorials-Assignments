@props(['photos' => [], 'defaults' => []])

{{-- CMS-1: three photos under the highlights. A photo uploaded in the admin
     is served from /media as WebP (see App\Support\SiteMedia); an empty slot
     shows the free-licence photo that ships in public/images/photos. They
     illustrate the kind of class, so never caption them as our own students
     or teachers. --}}
<div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3">
    @foreach ($defaults as $i => $default)
        @php
            $photo = (array) (array_values((array) $photos)[$i] ?? []);
            $image = is_string($photo['image'] ?? null) && $photo['image'] !== '' ? $photo['image'] : null;
            $small = $image ? \App\Support\SiteMedia::url($image, 640) : "/images/photos/{$default}-640.webp";
            $large = $image ? \App\Support\SiteMedia::url($image, 1200) : "/images/photos/{$default}-1200.webp";
        @endphp
        <img src="{{ $small }}"
             srcset="{{ $small }} 640w, {{ $large }} 1200w"
             sizes="(min-width: 768px) 33vw, {{ $loop->first ? '100vw' : '50vw' }}"
             alt="{{ $photo['alt'] ?? '' }}" width="640" height="480" loading="lazy" decoding="async"
             @class(['aspect-[4/3] w-full min-w-0 rounded-2xl object-cover', 'col-span-2 md:col-span-1' => $loop->first])>
    @endforeach
</div>
