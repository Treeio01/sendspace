<!DOCTYPE html>
<html lang="ru">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="keywords" content="file transfer, large file, big file, free send, send file">
  <meta name="description" content="Free file hosting. Upload and share large files instantly.">
  <title>@yield('title', 'SendSpace — Upload & Share Files')</title>
  <link type="text/css" rel="stylesheet" href="{{ asset('assets/sendspace.css') }}">
  <link rel="shortcut icon" href="{{ asset('assets/favicon.ico') }}">

  <script type="text/javascript" src="{{ asset('assets/jquery-1.12.4.min.js') }}"></script>
  <script type="text/javascript" src="{{ asset('assets/trn_javascript.html') }}"></script>
  <script type="text/javascript" src="{{ asset('assets/jquery-browser-deprecated.js') }}"></script>
  <link type="text/css" rel="stylesheet" href="{{ asset('assets/openid.css') }}">
  <link type="text/css" rel="stylesheet" href="{{ asset('assets/font-awesome.min.css') }}">

  <script type="text/javascript" src="{{ asset('assets/jquery-ui.min.js') }}"></script>
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/jquery-ui.min.css') }}">
  <link href="{{ asset('assets/jquery.tag-editor.css') }}" rel="stylesheet">

  @yield('head')
</head>

<body id="@yield('body_id', 'homepage_page')" class="desktop" lang="en">

  <div id="header" class="tar">
    <div class="grasp">
      @hasSection('show_illustration')
      <div id="illustration">
        <img src="{{ asset('assets/android_app_txt.png') }}" style="width:959px;height:382px" alt="SendSpace">
      </div>
      @endif

      <div class="wrap" style="margin:auto 30px">
        <div id="logo" class="tal">
          <a href="/">
            <img style="width:222px;height:42px" src="{{ asset('assets/logo.png') }}" alt="sendspace">
            <span id="userType"></span>
            <span id="slogan" style="clear:left;">Send, Receive, Track &amp; Share Your Big Files!</span>
          </a>
        </div>

        <nav>
          <ul id="control">
            <li style="position: relative; z-index:3">
              <a href="#" class="login" rel="open">Log In</a>
              <div id="login_frame" class="overlay">
                <a class="remove login" rel="close"></a> <a href="#" class="login close_label" rel="close">Log In</a>
                <div class="clear" style="height: 10px"></div>
                <form method="post" action="#" onsubmit="$('#glass').trigger('click'); return true" role="login">
                  <input type="hidden" name="action" value="login">
                  <input type="hidden" name="submit" value="login">
                  <input type="hidden" name="action_type" value="login">
                  <div class="row first">
                    <span class="label">Your Email or Username</span>
                    <input type="text" name="username" class="input" id="top_login_username" tabindex="1">
                  </div>
                  <div class="row">
                    <span class="label" style="width: auto !important;">Your Password <a style="font-weight: normal !important; display: inline-block; margin-left: 20px;" tabindex="5" href="#">Forgot your password?</a></span>
                    <input type="password" name="password" class="input" tabindex="2">
                  </div>
                  <div class="row" style="margin:3px 0">
                    <label style="float: left;line-height:36px;margin-left: 5px"><input tabindex="3" type="checkbox" class="checkbox" name="remember" checked="checked"> <span>Remember me</span></label>
                    <input style="float: right;margin: 0 0 0 2px" type="submit" class="sbtn" tabindex="4" value="Log In">
                  </div>
                </form>
              </div>
            </li>
            <li><a href="#">Sign Up</a></li>
            <li><a href="#">Plans</a></li>
            <li><a href="#">Tools</a></li>
            <li class="contact_us last"><a href="#">Contact Us</a></li>
          </ul>
        </nav>

        <div class="clear"></div>

        @yield('header_content')

      </div>
    </div>
  </div>

  @yield('main')

  <div id="footer">
    <div class="wrap limit-width">
      <ul id="footer_menu">
        <li>
          <ul>
            <li class="title"><i>send</i>space</li>
            <li><a href="#">About Us</a></li>
            <li><a href="#">Terms Of Use</a></li>
            <li><a href="#">Privacy Policy</a></li>
          </ul>
        </li>
        <li>
          <ul>
            <li class="title">Tools</li>
            <li><a href="#">Wizard (Win*, Mac, Linux)</a></li>
            <li><a href="#">SendSpace for Android</a></li>
            <li><a href="#">Developer Tools</a></li>
          </ul>
        </li>
        <li style="width:90px">
          <ul>
            <li class="title">Help &amp; Support</li>
            <li><a href="#">Contact Us</a></li>
            <li><a href="#" title="Frequently Asked Questions">FAQ</a></li>
          </ul>
        </li>
      </ul>

      <div id="signature">
        <div class="clear"></div>
        &copy; 2005-{{ date('Y') }} sendspace.com
      </div>
    </div>
  </div>

  <div id="glass"></div>

  <script type="text/javascript" src="{{ asset('assets/sendspace.js') }}"></script>
  <script type="text/javascript">
    $.ajaxSetup({
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
    window.ga = window.ga || function() {};
  </script>

  <div id="ssmsg_msg" onclick="return ssmsg.pop()">
    <div class="tac">
      <h1>Title</h1>
      <p>Alert</p>
      <div>
        <button class="sbtn">OK</button>
        <button class="sbtn caution">Cancel</button>
      </div>
    </div>
  </div>
  <div id="ssmsg_bg" onclick="return ssmsg.pop()">&nbsp;</div>

  @yield('scripts')

</body>
</html>
