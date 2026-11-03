@props(['videos', 'accent' => 'brand'])

{{-- Short videos from YouTube. Nothing loads from YouTube until the visitor
     taps play (resources/js/site.js swaps in a youtube-nocookie player), so
     the page stays fast and private. Only the video id is used from the link. --}}
<div class="grid gap-6 md:grid-cols-2">
    @foreach ($videos as $video)
        @php($id = \App\Support\YouTube::id($video['url'] ?? ''))
        @if ($id !== null)
            <figure class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div @class([
                    'relative aspect-video',
                    'bg-tuition-800' => $accent === 'tuition',
                    'bg-academic-800' => $accent === 'academic',
                    'bg-music-800' => $accent === 'music',
                    'bg-brand-800' => $accent === 'brand',
                ])>
                    <a href="https://www.youtube.com/watch?v={{ $id }}" target="_blank" rel="noopener" data-youtube="{{ $id }}" data-title="{{ $video['title'] }}"
                       class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-white">
                        <span @class([
                            'grid size-16 place-items-center rounded-full bg-white/95 shadow-lg',
                            'text-tuition-800' => $accent === 'tuition',
                            'text-academic-800' => $accent === 'academic',
                            'text-music-800' => $accent === 'music',
                            'text-brand-800' => $accent === 'brand',
                        ]) aria-hidden="true">
                            <svg class="ml-1 size-7" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </span>
                        <span class="px-4 text-center font-semibold">Play: {{ $video['title'] }}</span>
                    </a>
                </div>
                @if (filled($video['caption'] ?? null))
                    <figcaption class="p-4 text-sm text-slate-600">{{ $video['caption'] }}</figcaption>
                @endif
            </figure>
        @endif
    @endforeach
</div>
