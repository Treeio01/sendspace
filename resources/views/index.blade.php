@extends('layouts.app')

@section('title', 'SendSpace — Upload & Share Files')
@section('meta_description', 'SendSpace — бесплатный файлообменник. Загружайте файлы до 300MB и делитесь ими мгновенно. Drag & Drop загрузка.')
@section('og_title', 'SendSpace — Upload & Share Files')
@section('canonical_url', url('/'))
@section('body_id', 'homepage_page')
@section('show_illustration', true)

@section('header_content')
  <div id="progress_bar" class="dragbar downloading" style="height:auto; display:none; padding:30px">
    <p id="cur_file"></p>
    <div class="bar">
      <div class="frame"></div>
      <div class="fill"></div>
      <span class="tag">0%</span>
    </div>
    <div class="stats">
      <div class="col">
        <b>Remaining Time:</b> <span class="time_left">00:00:00</span> at <span class="kbps">0KB/s</span>
        <span class="elapsed">(<b>Elapsed:</b> <span>00:00:00</span>)</span>
      </div>
      <div class="col col2">
        Uploaded <b class="data">0KB</b> of <b class="total">0KB</b>
      </div>
    </div>
    <br>
    <button id="cancel_button" class="sbtn" style="margin:30px auto; display:block; clear:left;">
      <span style="position: relative; top: -2px;">Cancel Upload</span>
    </button>
  </div>

  <div id="start">
    <form role="main" method="post" action="{{ route('file.upload') }}" enctype="multipart/form-data"
      style="margin-left:-95px;margin-bottom:-10px" autocomplete="off">
      @csrf
      <input type="text" style="display:none">
      <input type="password" style="display:none">
      <input type="hidden" name="PROGRESS_URL" value="{{ route('file.progress') }}">
      <input type="hidden" name="js_enabled" id="js_enabled" value="1">
      <input type="hidden" name="upload_files" id="upload_files" value="">
      <input type="hidden" name="terms" value="1" id="terms">
      <div class="browse">
        <div class="start en"></div>
        <div class="browse-field">
          <input type="file" id="upload_file" name="upload_file[]" size="1" class="file" multiple="multiple" style="display:none">
          <div class="click">Drag files here or click Browse to upload</div>
          <label for="upload_file" class="sr-only">Select file to upload</label>
          <button class="sbtn" aria-label="Select file to upload"
            onclick="document.getElementById('upload_file').click();return false"><span
              class="desktop-only">Browse</span><span class="mobile-only">Upload</span></button>
        </div>
      </div>
      <style>
        #start .browse .browse-field {
          align-items: center;
          background: #fff;
          border: 2px solid #b8ddf3;
          border-radius: 6px;
          box-shadow: 0 2px 6px rgba(161, 215, 252, .35);
          box-sizing: border-box;
          display: inline-flex;
          height: 63px;
          left: 100px;
          max-width: calc(100% - 100px);
          min-width: 700px;
          padding: 0 8px 0 15px;
          position: absolute;
          top: 125px;
          width: auto
        }

        #start .browse .browse-field .click {
          flex: 1 1 auto;
          overflow: hidden;
          padding-right: 10px;
          position: static;
          text-overflow: ellipsis;
          white-space: nowrap
        }

        #start .browse .browse-field .sbtn {
          flex: 0 0 auto;
          position: static
        }

        @media screen and (max-width:768px) {
          .browse-field {
            width: 100% !important;
            position: initial !important;
            min-width: 0px !important;
            max-width: 100% !important;
          }
        }
      </style>
      <div class="clear" style="height: 70px"></div>
      <div class="select tal">
        <table>
          <tbody>
            <tr class="droid">
              <th colspan="2" style="font-size:16pt">Selected Files:</th>
            </tr>
            <tr class="hoverable">
              <td colspan="2">
                <label><input type="hidden" name="file[]"> <a class="remove" title="Remove File"></a> <span
                    class="filename">o:filename:o</span></label>
              </td>
            </tr>
            <tr>
              <td colspan="2" style="border:0" class="tar">
                <a class="add_more">
                  <input title="Add more files" placeholder="Add more files" type="file" id="newUpload"
                    name="upload_file[]" size="1" class="new_file" multiple="multiple">
                  <label aria-label="Add more files" for="newUpload">Add more files</label>
                </a>
              </td>
            </tr>
            <tr class="updesc">
              <td><label>Description (optional):</label></td>
              <td><textarea class="form_input" name="description[]" maxlength="200" rows="1" cols="80"></textarea></td>
            </tr>
            <tr id="maxSize">
              <td colspan="2">
                Max upload size: 300MB
              </td>
            </tr>
          </tbody>
        </table>

        <div class="send sendsection">
          <div>
            <div style="width:450px">
              <label aria-label="One or several email addresses to send the download information to, comma separated"
                for="recpemail" class="vam">To:</label>
              <input name="recpemail" id="recpemail"
                title="One or several email addresses to send the download information to, comma separated"
                autocomplete="off" type="hidden" class="tag-editor-hidden-src" value="">
            </div>
            <div style="margin-top:10px">
              <label aria-label="Your email address" for="ownemail" class="vam">From:</label>
              <input name="ownemail" id="ownemail" type="text" style="width:200px;border-radius:2px"
                placeholder="sender@email.com" class="vam form_input" value="">
            </div>
          </div>
          <div class="go">
            <input type="submit" class="submit sbtn large" value="Upload" title="Click to start upload">
            <br><br>Next step: Share or just store your files
          </div>
        </div>
        <div class="upload_terms tac"><em>* By uploading you confirm your files comply with our <a href="#"
              target="_blank" title="Terms of Service">Terms of Service</a>.</em></div>
      </div>
    </form>
  </div>

  <script type="text/javascript">
    var collection = [];
    var upload_form_max_upload_size = 314572800;
    var upload_form_drag_url = '{{ route("file.dragupload") }}';
    var upload_form_destination_dir = '1';
    var upload_form_too_big_msg = "File must be under 300MB.";

    $(function () {
      $('#js_enabled').val(1);
      $('#pwd').prop('type', 'password').val('');
      $('#ownemail').each(function () { });
      $('#recpemail').tagEditor({
        autocomplete: {
          delay: 0,
          position: { collision: 'flip' },
          source: window.collection || []
        },
        initialTags: ($('#recpemail').val() || '').split(','),
        maxTags: 50,
        delimiter: ',;',
        forceLowercase: false,
        sortable: false,
        placeholder: 'recipient@email.com',
        beforeTagSave: function (field, editor, tags, tag, val) {
          if (tags.length === 50 - 1) {
            (typeof ssmsg === 'undefined' ? window : ssmsg)['alert']('Limit of recipients per one message reached');
          }
          return val.indexOf('@') === -1 || val.indexOf('.') === -1 ? false : val;
        }
      });
    });
  </script>
@endsection

@section('main')
  <div id="headline">
    <h1 class="droid">Uploading</h1>
  </div>
  <div class="clear"></div>
  <div id="content" style="min-height: 0; padding: 0px 20px">
    <div class="uploading" id="uploading_progress" style="padding: 10px 20px">
      <center>
        <iframe name="progressbar_frame" id="frm_progressbar"
          style="overflow:hidden;border:0;display:none;width:800px;height:150px"
          src="{{ asset('assets/saved_resource.html') }}"></iframe>
        <div id="div_progressloading" style="width: 800px; font-size:22px; height: 150px;text-align:center;">
          <br><br><img style="width:16px;height:16px" src="{{ asset('assets/loading.gif') }}"
            alt="loading... please wait..."><br>
          <br>Starting upload... Please wait...
        </div>
        <button id="cancel_button" class="sbtn" onclick="cancelupload('/', 26)" style="margin: 5px;"><span
            style="position: relative; top: -2px;">Cancel Upload</span></button>
        <br>
      </center>
    </div>
  </div>
@endsection

@section('scripts')
  <script type="text/javascript" src="{{ asset('assets/jquery.caret.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('assets/jquery.tag-editor.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('assets/homepage.js') }}"></script>
@endsection