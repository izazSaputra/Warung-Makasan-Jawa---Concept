@php
    $v = $status->value;
    $ikon = ['menunggu_konfirmasi' => '&#9203;', 'diterima' => '&#10003;', 'disiapkan' => '&#9832;', 'siap_diambil' => '&#9873;', 'selesai' => '&#10003;', 'ditolak' => '&#10005;'][$v];
@endphp
<span class="status-badge status--{{ $v }}"><span aria-hidden="true">{!! $ikon !!}</span> {{ $status->label() }}</span>
