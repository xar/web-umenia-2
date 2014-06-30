@extends('layouts.master')

@section('title')
@parent
- {{ $item->name }}
@stop

@section('content')

<section class="item content-section top-section">
    <div class="item-body">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2 text-center">
                    <h2 class="uppercase">{{ $item->title }}</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 text-center">
                        <img src="{{ $item->getImagePath() }}" class="img-responsive">
                        <p>identifikátor: {{ $item->id }}</p>
                </div>
                <div class="col-md-4 text-left">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <td class="atribut">autor:</td>
                                    <td>{{ $item->author; }}</td>
                                </tr>
                                <tr>
                                    <td class="atribut">datovanie:</td>
                                    <td>{{ $item->dating; }}</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="atribut">výtvarný druh:</td>
                                    <td>{{ $item->work_type; }}</td>
                                </tr>
                                <tr>
                                    <td class="atribut">stupeň spracovania:</td>
                                    <td>{{ $item->work_level; }}</td>
                                </tr>
                                <tr>
                                    <td class="atribut">žáner:</td>
                                    <td>{{ $item->topic; }}</td>
                                </tr>
                                @if (!empty($item->place))
                                <tr>
                                    <td class="atribut">motív:</td>
                                    <td>{{ $item->subject; }}</td>
                                </tr>
                                @endif
                                @foreach ($item->measurements as $key => $measurement)
                                    <tr>
                                        <td class="atribut">{{ $key }}:</td>
                                        <td>{{ $measurement }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="atribut">materiál:</td>
                                    <td>{{ $item->medium; }}</td>
                                </tr>
                                <tr>
                                    <td class="atribut">technika:</td>
                                    <td>{{ $item->technique; }}</td>
                                </tr>
                                <tr>
                                    <td class="atribut">značenie:</td>
                                    <td>{{ $item->inscription; }}</td>
                                </tr>
                                @if (!empty($item->place))
                                <tr>
                                    <td class="atribut">geografická oblasť:</td>
                                    <td>{{ $item->place; }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="atribut">stupeň spracovania:</td>
                                    <td>{{ $item->state_edition; }}</td>
                                </tr>
                                @if (!empty($item->integrity))
                                <tr>
                                    <td class="atribut">stupeň integrity:</td>
                                    <td>{{ $item->integrity; }}</td>
                                </tr>
                                @endif
                                @if (!empty($item->integrity_work))
                                <tr>
                                    <td class="atribut">integrita s dielami:</td>
                                    <td>{{ $item->integrity_work; }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="atribut">galéria:</td>
                                    <td>{{ $item->gallery; }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- <div id="map"></div> -->

@stop