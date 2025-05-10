<div class="flex items-start max-md:flex-col">
   

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full {{ $width??'max-w-lg'}}">
            {{ $slot }}
        </div>
    </div>
</div>
