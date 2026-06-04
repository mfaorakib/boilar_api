@component('mail::message')
# Contact Mail

Name: {{$body['name']}}

Email: {{$body['email']}}

{{$body['message']}}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
