@extends('layouts.app')

@section('title', 'Download ' . $file->original_name . ' from SendSpace')
@section('body_id', '_page')

@section('meta_description', ($file->description ?: 'Скачать файл ' . $file->original_name . ' (' . $file->formatted_size . ') с SendSpace'))
@section('og_title', $file->original_name . ' (' . $file->formatted_size . ') — SendSpace')
@section('canonical_url', url('/file/' . $file->download_token))
@section('og_type', 'article')

@section('head')
@endsection

@section('header_content')
@endsection

@section('main')
  <div class="centered limit-width">
    <div id="headline">
      <h1 class="droid">Download</h1>
      <div class="actions file">
        <a href="/" class="button upload_new_file">&gt; Upload New File</a>
      </div>
    </div>

    <div class="clear"></div>
    <div id="content" class="download_with_ads">
      <div class="info">
        <div style="padding-top:10px;min-height:320px">
          <div style="padding:0;margin:0 10px;width:520px">

            <div style="display: table; width:100%;">
              <h2 class="bgray" style="display: table-cell;"><b>{{ $file->original_name }}</b></h2>
            </div>

            <div class="file_description reverse margin_center">
              <b>File Size:</b> {{ $file->formatted_size }}
            </div>

            @if($file->description)
              <p><b>Description:</b> {{ $file->description }}</p>
            @endif

            <br>
            <div class="text dlact_wrap">
              <div id="spndllink">
                <div class="dlfile_actions" role="main">
                  <a id="download_button" class="download_page_button button1"
                    href="{{ route('file.download', $file->download_token) }}"
                    title="Click here to download, {{ $file->original_name }}"
                    tabindex="0" accesskey="l" role="button">Download</a>
                </div>
                <br>
              </div>
            </div>

          </div>
        </div>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
    </div>

    <noscript>
      <div id="nojs" style='border: 1px solid #F7941D; background: #FEEFDA; text-align: center; clear: both; height: 75px; position: relative;'>
        <div style='width: 640px; margin: 0 auto; text-align: left; padding: 0; overflow: hidden; color: black;'>
          <div style='width: 100%; float: left; font-family: Arial, sans-serif;'>
            <div style='width: 75px; float: left;'><img src='/graphics/ie6nomore/ie6nomore-warning.jpg' alt="Warning!" /></div>
            <div style='font-size: 14px; font-weight: bold; margin-top: 12px;'>No javascript available!</div>
            <div style='font-size: 12px; margin-top: 6px; line-height: 12px;'>Your browser does not support JavaScript or JavaScript is disabled.</div>
          </div>
        </div>
      </div>
    </noscript>
  </div>
@endsection
