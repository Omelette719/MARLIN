{{--
    flux:table already wraps its <table> in a horizontally-scrollable
    container (ui-table-scroll-area), but nothing about a table with its
    edges simply cut off at the card boundary signals that swiping reveals
    more columns — lg:hidden since a table with this many columns almost
    always already fits without scrolling from that breakpoint up.
--}}
<div class="mb-2 flex items-center gap-1.5 text-xs text-zinc-500 lg:hidden">
    <flux:icon icon="arrows-right-left" class="size-3.5 shrink-0" />
    Geser tabel ke kiri/kanan untuk melihat kolom lainnya.
</div>
