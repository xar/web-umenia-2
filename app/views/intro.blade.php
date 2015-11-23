@extends('layouts.master')

@section('title')
@parent

@stop

@section('link')
<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "WebSite",
    "logo": " {{ asset('images/logo.png') }}",
    "url": "{{ URL::to('') }}",
    "sameAs" : [
        "https://www.facebook.com/webumenia.sk",
        "https://twitter.com/webumeniask",
        "http://webumenia.tumblr.com/",
        "https://vimeo.com/webumeniask",
        "https://sk.pinterest.com/webumeniask/",
        "https://instagram.com/web_umenia/"
    ],
    "potentialAction": {
      "@type": "SearchAction",
      "target": "{{ URL::to('katalog') }}/?search={query}",
      "query-input": "required name=query"
    }
}
</script>
@stop

@section('content')

    <div id="webumeniaCarousel" class="carousel slide carousel-fade" data-ride="carousel">
      {{-- <div class="container"> --}}
      <!-- Indicators -->
        <ol class="carousel-indicators">
          <li data-target="#webumeniaCarousel" data-slide-to="0" class="active"></li>
          <li data-target="#webumeniaCarousel" data-slide-to="1"></li>
          <li data-target="#webumeniaCarousel" data-slide-to="2"></li>
          <li data-target="#webumeniaCarousel" data-slide-to="3"></li>
        </ol>

      <div class="carousel-inner" role="listbox">

        <div class="item active">
            <a href="/kolekcia/45" class="header-image text-center" style="background-image: url(/images/kolekcie/jarmoky.jpg); text-shadow:0px 1px 0px #777; color: #fff">
                <div class="header-body">
                    <h2>kolekcia</h2>
                    <h1>Jarmočná jeseň</h1>
                </div>
            </a>
        </div>

        <div class="item">
            <a href="/kolekcia/42" class="header-image text-center" style="background-image: url(/images/kolekcie/42.jpg); text-shadow:0px 1px 0px #777; color: #fff">
                <div class="header-body">
                    <h2>kolekcia</h2>
                    <h1>Portréty Habsburgovcov</h1>
                    <h2>v zbierkach GMB</h2>
                </div>
            </a>
        </div>

        <div class="item ">
            <a href="/katalog?search=jese%C5%88&author=&work_type=maliarstvo&tag=&gallery=&topic=&technique=&has_image=1&year-range=600%2C2015&sort_by=" class="header-image text-center" style="background-image: url(/images/kolekcie/jesen.jpg); text-shadow:0px 1px 0px #777; color: #fff">
                <div class="header-body">
                    <h1>Jeseň</h1>
                    <h3>farby a nálady jesene na plátnach maliarov</h3>
                </div>
            </a>
        </div>

        <div class="item">
            <a href="/kolekcia/34" class="header-image text-center" style="background-image: url(/images/kolekcie/34.jpg); text-shadow:0px 1px 0px #777; color: #fff">
                <div class="header-body">
                    <h2>kolekcia</h2>
                    <h1>Zbierka grófa Jána Pálffyho </h1>
                    <h2>v Slovenskej národnej galérii</h2>
                </div>
            </a>
        </div>

      </div>
      {{-- <div class="pull-center">
        <a class="carousel-control left" href="#webumeniaCarousel" data-slide="prev">‹</a>
        <a class="carousel-control right" href="#webumeniaCarousel" data-slide="next">›</a>
      </div> --}}
    </div>

<section class="articles light-grey content-section">
    <div class="articles-body">
        <div class="container">
            <div class="row">
            	@foreach ($articles as $i=>$article)
	                <div class="col-sm-6 col-xs-12 bottom-space">
	                	<a href="{{ $article->getUrl() }}" class="featured-article">
	                		<img src="{{ $article->getThumbnailImage() }}" class="img-responsive" alt="{{ $article->title }}">
	                	</a>
                        <a href="{{ $article->getUrl() }}"><h4 class="title">
                            @if ($article->category)
                                {{ $article->category->name }}:
                            @endif
                            {{ $article->title }}
                        </h4></a>
                        <p class="attributes">{{ $article->getShortTextAttribute($article->summary, 250) }}
                        (<a href="{{ $article->getUrl() }}">viac</a>)
                        </p>
                        <p class="meta">{{$article->published_date}} / {{$article->author}}</p>
	                    
	                </div>
                    @if ($i%2 == 1)
                        <div class="clearfix"></div>
                    @endif
            	@endforeach
            </div>
        </div>
    </div>
</section>

<div class="container text-center top-space">
    <div class="fb-like" data-href="{{ Config::get('app.url') }}" data-layout="button_count" data-action="like" data-show-faces="false" data-share="false"></div>
    &nbsp;
    <a href="https://twitter.com/share" class="twitter-share-button" data-count="true">Tweet</a>
    <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
</div>

@stop

@section('javascript')

<script type="text/javascript">

</script>
@stop
