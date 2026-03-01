@extends('layouts.app')

@section('title', 'Upload Complete — SendSpace')
@section('body_id', 'upload_complete_page')

@section('header_content')
@endsection

@section('main')
  <div id="headline">
    <h1 class="droid">Upload Finished</h1>
    <div class="actions uploaddone">
      <a href="/" class="button upload_new_file">&gt; Upload New File</a>
    </div>
  </div>
  <div class="clear"></div>
  <div id="content">
  <div class="centered limit-width">
    <div class="upload_complete">
      <h2 style="border-bottom: 1px solid #E5E5E5; width: 620px; padding-bottom: 1em;" class="bgray">
        <b>Congratulations!</b> Your upload finished successfully</h2>

      @if($files->first()?->uploader_email)
        <p>All the below information has been emailed to you at <b>{{ $files->first()->uploader_email }}</b></p>
      @endif
      @if($files->first()?->recipient_email)
        <p>Download links were sent to <b>{{ $files->first()->recipient_email }}</b></p>
      @endif

      <div id="tabParent" role="main">
        <div class="col col1">
          <div id="tab_container">
            @foreach($files as $file)
            <div id="tab{{ $file->download_token }}" class="tab" style="display: block;">
              <div class="file_description">

                <h2 style="font-size:16px; font-weight:bold;">
                  <a href="{{ route('file.show', $file->download_token) }}" target="_blank" title="Download Page Link">{{ $file->original_name }}</a>
                  (<small>{{ $file->formatted_size }}</small>)
                </h2>

                @if($file->description)
                  <p><strong>Description:</strong> {{ $file->description }}</p>
                @endif

                <h4>Download Page Link</h4>
                <div class="urlbox small">
                  <a aria-label="Download Page Link" href="{{ route('file.show', $file->download_token) }}" target="_blank"
                    class="share link" id="cpsrc{{ $file->id }}link">{{ url('/file/' . $file->download_token) }}</a>
                  <div class="copyContainer" style="position:relative" data-clipboard-action="copy"
                    data-clipboard-target="#cpsrc{{ $file->id }}link">
                    <div class="copyButton">Copy Link</div>
                  </div>
                </div>
                <p class="urlbox-desc">
                  Share this link with anyone to let them download your file.
                </p>

                <h4>Direct Download Link</h4>
                <div class="urlbox small">
                  <a aria-label="Direct Download Link" href="{{ route('file.download', $file->download_token) }}" target="_blank"
                    class="share link" id="cpsrc{{ $file->id }}direct">{{ url('/file/' . $file->download_token . '/download') }}</a>
                  <div class="copyContainer" style="position:relative" data-clipboard-action="copy"
                    data-clipboard-target="#cpsrc{{ $file->id }}direct">
                    <div class="copyButton">Copy Link</div>
                  </div>
                </div>
                <p class="urlbox-desc">
                  Direct download link will start the download immediately.
                </p>

                <h4><a aria-label="Toggle for more link types" class="urlbox_toggle" href="#">Show HTML &amp; Forum Links</a></h4>
                <div class="urlbox_wrapper" style="display:none">
                  <h4>HTML Code</h4>
                  <div class="urlbox small">
                    <input title="HTML Code for use in websites" type="text"
                      value='<a title="Download from SendSpace" href="{{ url('/file/' . $file->download_token) }}" target="_blank">Download {{ $file->original_name }} from SendSpace</a>'
                      class="void link" readonly id="cpsrc{{ $file->id }}html">
                    <div class="copyContainer" style="position:relative" data-clipboard-action="copy"
                      data-clipboard-target="#cpsrc{{ $file->id }}html">
                      <div class="copyButton code">Copy Code</div>
                    </div>
                  </div>

                  <h4>Forum Code</h4>
                  <div class="urlbox small">
                    <input title="Forum Code" type="text" value="[url]{{ url('/file/' . $file->download_token) }}[/url]"
                      class="link void" readonly id="cpsrc{{ $file->id }}forum">
                    <div class="copyContainer" style="position:relative" data-clipboard-action="copy"
                      data-clipboard-target="#cpsrc{{ $file->id }}forum">
                      <div class="copyButton code">Copy Code</div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
            @endforeach
          </div>
        </div>
      </div>

      <br clear="all">
    </div>
    <div class="clear"></div>
  </div>
  </div>
@endsection

@section('scripts')
<script>
  $(function () {
    $(".tab").hide();
    $("ul.uploadTabs li:first").addClass("active").show();
    $(".tab:first").show();

    $('.file_description').on('click', 'a.urlbox_toggle', function (e) {
      $(this).parent().next().toggle();
      return false;
    });

    $('.copyContainer').on('click', function () {
      var target = $(this).attr('data-clipboard-target');
      var el = $(target)[0];
      var text = '';

      if (el.tagName === 'INPUT') {
        text = el.value;
      } else {
        text = el.textContent || el.innerText;
      }

      var btn = $(this).find('.copyButton');
      navigator.clipboard.writeText(text).then(function() {
        btn.text('Copied');
        setTimeout(function() { btn.text('Copy Link'); }, 2000);
      });
    });

    $("ul.uploadTabs li").click(function (e) {
      $("ul.uploadTabs li").removeClass("active");
      $(this).addClass("active");
      $(".tab").hide();
      $($(this).find("a").attr("href")).fadeIn('slow').find('hr').remove();
      e.preventDefault();
    });

    $('.link.void').click(function (e) {
      e.preventDefault();
    });
  });
</script>
@endsection
