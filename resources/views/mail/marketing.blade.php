<x-mail::message>
# {{ $email->subject }}

{{ $email->body }}

<x-mail::subcopy>
You get this because you asked for news and offers from {{ config('app.name') }}. [Unsubscribe]({{ $unsubscribeUrl }}) with one click.
</x-mail::subcopy>
</x-mail::message>
