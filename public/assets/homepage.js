var is_email = function (email) {
    return email.length > 2 && (email.indexOf('@') !== -1 && email.indexOf('@') + 1 !== email.length);
};

$(function () {
    var $start = $("#start");
    var slot = $start.find("tr.hoverable").eq(0).html();
    var max_upload_size = upload_form_max_upload_size;
    var objUpload = null;

    var UPLOAD_ERROR_MSG_TOO_BIG = trn('sorry, {file} too big', "Sorry, 'o:filename:o' is too big.") + upload_form_too_big_msg;
    var UPLOAD_ERROR_MSG_DUPLICATE = trn('{file} already selected, choose other', "'o:filename:o' has already been selected.\nPlease choose another file.");

    var shorten_name = function (name, length) {
        var name = name.split("\\");
        if (length && name[name.length - 1].length > length) {
            return name[name.length - 1].substr(0, length - 7) + "..." + name[name.length - 1].substr(name[name.length - 1].length - 7);
        }
        return  name[name.length - 1];
    };

    var uploads = function () {

        var self = this;
        var target = null;
        var dir_id = null;
        var filesData = [];

        var statusEnum = {
            PENDING: 0,
            UPLOADING: 1,
            COMPLETED: 2
        };

        this.universalProgress = {fileCount: 0, uploadedCount: 0, totalBytes: 0, bytesUploaded: 0};

        var counter = 0;

        /* Progress */
        var last_update = 0;
        var last_bytes = 0;
        var speed = 0;
        var started = 0;

        this.init = function () {


        };

        this.setURL = function (url) {
            self.target = url;
        };

        this.setDirID = function (x) {
            self.dir_id = x;
        };

        this.isUploading = function () {
            return self.universalProgress.uploadedCount !== self.universalProgress.fileCount;
        };

        this.queue = function (file) {

            var iSize = typeof file['size'] !== 'undefined' ? file['size'] : file['fileSize'];
            var name = typeof file['name'] !== 'undefined' ? file['name'] : file['fileName'];

            var found = false;

            for (var i = 0; i < filesData.length; i++) {

                if (typeof filesData[i] === "undefined" || !filesData[i])
                    continue;

                if (filesData[i].size === iSize && filesData[i].obj.name === name) {
                    found = true;
                    break;
                }

            }

            if (!found) {
                var id = counter++;

                filesData.push({uid: id, obj: file, size: iSize, bytesUploaded: 0, status: statusEnum.PENDING, xhr: null, hash: null});

                self.universalProgress.totalBytes += file['size'];
                self.universalProgress.fileCount++;

                return id;
            }

            return -1;
        };

        this.remove = function (n) {
            var obj = filesData[n];

            if (!obj || obj === undefined)
                return;

            switch (obj.status) {
                case 1:
                    obj.xhr.abort();
                    break;
                case 2:
                    self.universalProgress.uploadedCount--;
                    self.universalProgress.bytesUploaded -= obj.size;
                    break;
            }

            self.universalProgress.totalBytes -= obj.size;
            self.universalProgress.fileCount--;

            //filesData.splice(n, 1);

            filesData[n] = null;

            self.updateProgressBar();

            if (!self.getFileCount())
                sendspace.progress.restoreTitle();
        };

        this.uploadNext = function () {
            if (!filesData.length)
                return;

            var obj = null;

            for (var i = 0; i < filesData.length; i++) {

                if (!filesData[i])
                    continue;

                obj = filesData[i];

                if (obj.status === statusEnum.UPLOADING)
                    break;

                if (obj.status === statusEnum.PENDING)
                    break;
            }

            if (obj !== null && obj.status !== statusEnum.COMPLETED && obj.status !== statusEnum.UPLOADING)
                self.upload(obj);
        };

        this.upload = function (cur) {
            var self = this;
            var file = cur.obj;
            var xhr = new XMLHttpRequest();
            var name = (file['name'] !== undefined) ? file['name'] : file['fileName'];

            if (self.universalProgress.uploadedCount === 0)
                started = new Date().getTime() / 1000;

            filesData[cur.uid].status = statusEnum.UPLOADING;
            filesData[cur.uid].xhr = xhr;

            self.updateProgressBar();

            $('#cur_file').html('<strong>Uploading:</strong> ' + name);

            xhr.upload.addEventListener('progress', function (evt) {
                if (evt.lengthComputable) {
                    cur.bytesUploaded = evt.loaded;

                    self.updateProgressBar();

                    /* seems sometimes there's a delay before the "load" event being called */
                    if (cur.bytesUploaded === cur.size) {
                        self.uploadNext();
                        filesData[cur.uid].status = statusEnum.COMPLETED;
                    }
                }
            }, false);

            xhr.addEventListener('load', function (e) {
                self.universalProgress.bytesUploaded += cur.size;
                self.universalProgress.uploadedCount++;

                filesData[cur.uid].status = statusEnum.COMPLETED;

                self.uploadNext();
                self.updateProgressBar();

                var done = 0;
                var total = 0;
                for (var i = 0; i < filesData.length; i++) {

                    if (!filesData[i])
                        continue;

                    obj = filesData[i];
                    total++;
                    if (obj.status === statusEnum.COMPLETED)
                        done++;
                }

                if (done === total)
                    $start.find("form").submit();

                /*if (self.getOverallProgress() >= 100) {
                 $start.find("form").submit();
                 }*/
            }, false);

            xhr.onreadystatechange = function (e) {
                if (xhr.readyState === 4) {
                    switch (xhr.status) {
                        case 200:
                            var hash = xhr.responseText;
                            filesData[cur.uid].hash = hash;
                            $('#start form').find('input[name="file[]"]').each(function () {
                                if (parseInt($(this).val(), 10) === cur.uid) {
                                    $(this).next().val(hash);
                                }
                            });
                            break;

                        case 403:
                            var patt = / e:(\d*)/g;
                            var result = patt.exec(xhr.responseText);
                            var e = 0;
                            if (result)
                                e = result[1];
                            document.location.href = '/?err=3&e=' + e;
                            break;
                    }
                }
            };

            var data = new FormData();

            data.append("fileField", file);

            //xhr.open('POST', self.target + '?DESTINATION_DIR='+self.dir_id, true);
            xhr.open('POST', self.target, true);

            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken.getAttribute('content'));
            }
            xhr.setRequestHeader("X-File-Name", unescape(encodeURIComponent(name)));
            xhr.setRequestHeader('X-File-Size', cur.size);
            xhr.setRequestHeader('X-File-Type', file['type']);
            xhr.setRequestHeader('X-File-ID', cur.uid);
            xhr.setRequestHeader('X-Dir-ID', self.dir_id);

            xhr.send(data);
        };

        this.getProgress = function (cur) {
            var pct = (cur.bytesUploaded / cur.size) * 100;
            pct = Math.round(pct * Math.pow(10, 0)) / Math.pow(10, 0);
            return pct;
        };

        this.getFileObj = function (n) {
            for (var i = 0; i < filesData.length; i++) {

                if (filesData[i] === null)
                    continue;

                var obj = filesData[i];
                if (obj.uid === n)
                    return obj;
            }
        };

        this.getOverallProgress = function (n) {

            var currentUploaded = 0;
            var time = new Date().getTime() / 1000;
            var diff = last_update > 0 ? time - last_update : 0;

            for (var i = 0; i < filesData.length; i++) {
                if (filesData[i] === null)
                    continue;

                if (filesData[i].status === statusEnum.UPLOADING || filesData[i].status === statusEnum.COMPLETED)
                    currentUploaded += filesData[i].bytesUploaded;
            }

            if (diff >= 2 || last_update === 0) {
                last_update = time;
                var bytes = (currentUploaded - last_bytes) / 1000;
                last_bytes = currentUploaded;
                speed = (diff === 0 ? 0 : (bytes * 1000) / diff);
            }

            //var totalPct = ((self.universalProgress.bytesUploaded+currentUploaded) / self.universalProgress.totalBytes) * 100;
            //var totalPct = (self.universalProgress.bytesUploaded / self.universalProgress.totalBytes) * 100;
            var totalPct = ((currentUploaded) / self.universalProgress.totalBytes) * 100;
            totalPct = Math.round(totalPct * Math.pow(10, 0)) / Math.pow(10, 0);

            return totalPct;
        };

        this.updateProgressBar = function () {
            sendspace.progress.move(self.getOverallProgress());

            var elapsed = Math.round((new Date().getTime() / 1000) - started);

            var remaining_bytes = (self.universalProgress.totalBytes - last_bytes);
            var remaining = remaining_bytes / speed;

            var seconds = Math.round((elapsed % 60));
            var hours = Math.round(((elapsed - seconds) / 3600));
            var minutes = Math.round((((elapsed - seconds) / 60) % 60));

            if (seconds < 10)
                seconds = '0' + seconds;
            if (hours < 10)
                hours = '0' + hours;
            if (minutes < 10)
                minutes = '0' + minutes;

            var elapsed = hours + ':' + minutes + ':' + seconds;

            seconds = Math.round((remaining % 60));
            hours = Math.round(((remaining - seconds) / 3600));
            minutes = Math.round((((remaining - seconds) / 60) % 60));

            if (seconds < 10)
                seconds = '0' + seconds;
            if (hours < 10)
                hours = '0' + hours;
            if (minutes < 10)
                minutes = '0' + minutes;

            var eta = hours + ':' + minutes + ':' + seconds;

            if (remaining_bytes > 0 && last_bytes > 0) {
                $('#progress_bar .kbps').text(bytesToSize(speed) + trn('per second', '/s') + ' ');
                $('#progress_bar .data').text(bytesToSize(last_bytes));
                $('#progress_bar .total').text(bytesToSize(self.universalProgress.totalBytes));

                $('#progress_bar .time_left').text(eta);
                $('#progress_bar .elapsed span').text(elapsed);
            }
        };

        this.getFileCount = function () {

            if (!filesData.length)
                return 0;

            var count = 0;

            for (var i = 0; i < filesData.length; i++) {
                if (filesData[i] !== null)
                    count++;
            }

            return count;
        };
    };


    try
    {
        var xhr = new XMLHttpRequest();
    } catch (e) {
    }

    if (window.File && window.FileList && window.FileReader && xhr.upload) {
        if (!$('#start .click').attr('error'))
        {
            $('#start .click').text($('#start .click').parents('form').find('[multiple]').length ?
                    trn('upload prompt plural', 'Drag files here or click browse to upload') :
                    trn('upload prompt singular', 'Drag file here or click browse to upload')
                    );
            if ($('#user_file_versions_page').length)
                $('#start .click').text(trn('upload prompt versioning', 'Drag here or click to upload new version'));
        }
        $('#cloud').fadeIn('slow');

        if (!$('.upload_disabled').length) {
            var dragarea = $('body')[0];

            dragarea.addEventListener('dragover', HandleDragHover, false);
            dragarea.addEventListener('dragleave', HandleDragOut, false);
            dragarea.addEventListener('drop', HandleDragDrop, false);

            objUpload = new uploads();
            objUpload.init();

            objUpload.setURL(upload_form_drag_url);
            objUpload.setDirID(upload_form_destination_dir);
        }
    }

    function checkMulti() {
        var ctrl = $('a.button #upload_file, .add_more .new_file');

        if (window.FileList) {
            ctrl.attr('multiple', '');
        }
    }


    function checkHasFiles() {
        if (!objUpload.getFileCount()) {
            $('#dragndrop').fadeOut(function () {
                $('#start').fadeIn(function () {
                    $('#cloud').fadeIn();
                });
            });
        }
    }

    function HandleDragOut() {
        checkHasFiles();

        if (!$('.select:visible').length) {
            $('a.button').append($('<input type="file" />').attr('id', 'upload_file').attr('name', 'upload_file[]').attr('size', 1).addClass('file'));
            checkMulti();
        }
        dragarea.removeEventListener('mouseout', HandleDragOut, false);
    }

    function HandleDragHover(e) {
        e.stopPropagation();
        e.preventDefault();

        $('#upload_file').remove();

        dragarea.addEventListener('mouseout', HandleDragOut, false);
    }

    function HandleDragDrop(e) {
        var files = e.target.files || e.dataTransfer.files;

        if (typeof files === 'undefined')
            return;
        e.stopPropagation();
        e.preventDefault();

        for (var i = 0, file; file = files[i++]; ) {
            uploadFile(file);
        }
    }

    function uploadFile(file) {

        var name = typeof file['name'] !== 'undefined' ? file['name'] : file['fileName'];
        var iSize = typeof file['size'] !== 'undefined' ? file['size'] : file['fileSize'];
        var sType = typeof file['type'] !== 'undefined' ? file['type'] : file['fileType'];

        if (sType === '' && (iSize === 0 || iSize === 4096))
        {
            ssmsg.alert(trn('sorry, {name} is folder', "Sorry, o:name:o is a folder and cannot be uploaded").replace('o:name:o', "'" + name + "'"));
            checkHasFiles();
            return;
        }

        if (iSize > max_upload_size) {
            ssmsg.alert(UPLOAD_ERROR_MSG_TOO_BIG.replace("o:filename:o", name));
            checkHasFiles();
            return;
        }

        var uid = objUpload.queue(file);

        if (uid === -1) {
            ssmsg.alert(UPLOAD_ERROR_MSG_DUPLICATE.replace('o:filename:o', name));
            checkHasFiles();
            return;
        }

        //var size = bytesToSize(iSize);
        //var type = file['type'];
        //var lastMod = (file['lastModified'] === undefined) ? '' : (file['lastModified']);

        var filename = shorten_name(name, 35);
        var fullname = name;

        var nameinput = $('<input />')
                .attr('type', 'hidden')
                .attr('name', 'name[]')
                .attr('value', name);

        var fileinput = $('<input />')
                .attr('type', 'hidden')
                .attr('name', 'file[]')
                .attr('value', uid);

        var hashinput = $('<input />')
                .attr('type', 'hidden')
                .attr('name', 'hash[]');

        if (objUpload.getFileCount() === 1 && $start.find("tr.hoverable").length) {
            $start.find('form').find('input[name="file[]"]').remove();
            $start.find('form').append($('<input type="hidden" />').attr('name', 'is_drag').val(1));
            $start.find(".browse").hide();
            $start.find(".select").show()
                    .find('input[name=password]').val('');/*prevent autocomplete*/
            $('.add_more')
                    .replaceWith(
                            $('<span />')
                            .css({'display': 'block', 'text-align': 'center', 'padding': '20px 0', 'font-weight': 'bold'})
                            .text(trn('can drag more', 'You can drag more files in here.'))
                            );

            $start.find("tr.hoverable").data('full', fullname).data('uid', uid);
            $start.find("tr.hoverable .filename").text(filename).attr("title", fullname).append(nameinput).append(fileinput).append(hashinput);
            $start.find("tr.hoverable").append($(".browse .file").hide());

            select_handler.add(fullname);

        } else if (select_handler.count() >= 20) {
            ssmsg.alert(trn('maximum files added', 'You have added the maximum amount of files'));
        } else if (select_handler.add(fullname)) {
            var $clone = $("<tr/>")
                    .addClass("hoverable")
                    .data('full', fullname)
                    .data('uid', uid)
                    .html(slot.replace("o:filename:o", filename)).attr("title", fullname).append(nameinput).append(fileinput).append(hashinput);

            if ($start.find('tr.hoverable').length)
                $clone.insertAfter($start.find("tr.hoverable").last());
            else
                $clone.insertAfter($start.find("tr.droid").first());

        } else if (navigator.userAgent.match(/(msie) ([\w.]+)/i)) { /* IE get called only when focus is lost, and then cause the alert to pop twice */
        } else
            ssmsg.alert(trn('file was already selected', 'File was already selected'));
    }

    function bytesToSize(bytes) {
        var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        if (parseInt(bytes, 10) === 0)
            return 'n/a';
        var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
        return Math.round(bytes / Math.pow(1024, i), 2) + trn(sizes[i] + ' unit', sizes[i]);
    }

    /**
     * first file.
     */
    $start.find(".browse .file").change(function ()
    {
        if (window.FileList) {
            var dragarea = $('body')[0];
            dragarea.removeEventListener('dragover', HandleDragHover, false);
            dragarea.removeEventListener('dragleave', HandleDragOut, false);
            dragarea.removeEventListener('drop', HandleDragDrop, false);
        }

        if (window.FileList && this.files && this.files.length > 1) {

            for (var i = 0; i < this.files.length; i++) {
                var name = this.files[i].name;
                if (select_handler.has(name))
                    return ssmsg.alert(UPLOAD_ERROR_MSG_DUPLICATE.replace('o:filename:o', name));
            }

            for (var i = 0; i < this.files.length; i++) {
                var name = this.files[i].name;
                var filename = shorten_name(name, 35);

                var size = this.files[i].size ? this.files[i].size : 0;

                if (size > max_upload_size) {
                    ssmsg.alert(UPLOAD_ERROR_MSG_TOO_BIG.replace("o:filename:o", name));
                    continue;
                }

                if (!select_handler.add(name)) {
                    ssmsg.alert(trn('error adding {file}, retry', 'An error occurred adding: o:name:o. Please try again.').replace('o:name:o', name));
                } else if (i) {
                    var $clone = $("<tr/>")
                            .addClass("hoverable")
                            .data('full', $(this).val())
                            .html(slot.replace("o:filename:o", filename)).attr("title", name);

                    $clone.find('a.remove').remove();

                    $clone
                            .insertAfter($start.find("tr.hoverable, tr.droid").last());
                } else {
                    $start.find("tr.hoverable").data('full', name);
                    $start.find("tr.hoverable .filename").text(filename).attr("title", name);
                    $start.find("tr.hoverable").append($(".browse .file").hide());
                    $start.find('a.remove').remove();
                }
            }
        } else {
            var filename = shorten_name($(this).val(), 35);
            $start.find("tr.hoverable").data('full', $(this).val());
            $start.find("tr.hoverable .filename").text(filename).attr("title", shorten_name($(this).val(), false));
            $start.find("tr.hoverable").append($(".browse .file").hide());
            select_handler.add($(this).val());
        }

        // show files list only if we got at least 1 file
        if (select_handler.count() > 0)
        {
            $start.find(".browse").hide();
            $start.find(".select").show()
                    .find('input[name=password]').val('');/*prevent autocomplete*/
        }
    });

    /**
     * Files selector table.
     */

//    $('.select .send input[type=text], .click_to_add').each(function () {
//        $(this).data("oval", $(this).val());
//    });

    $start.find(".select .click_to_add, .select .send input[type=text]").on('focus', function ()
    {
        if ($(this).parent().parent().hasClass("hoverable"))
            $(this).parent().parent().addClass("hover");

        /* if (typeof ($(this).data("first")) === "undefined" || !$(this).data("first"))
         {
         $(this)
         .data("oval", $(this).val())
         .data("first", 1)
         .val("")
         .addClass("clicked");
         }*/
    });

    $start.find(".select .click_to_add, .select .send input").on('blur', function ()
    {

        if ($(this).parent().parent().hasClass("hoverable"))
            $(this).parent().parent().addClass("hover");

        if ($(this).parent().parent().hasClass("hover"))
            $(this).parent().parent().removeClass("hover");

        if ($(this).attr('id') === 'ownemail' && !is_email($(this).val()))
            $(this).val('');

        /*        if ($(this).val() == '')
         {
         $(this)
         .val($(this).data("oval"))
         .removeData("first")
         .removeClass("clicked");
         }*/
    });

    $start.find(".submit").on('click', function (e)
    {
        if ($('#remotefile').length)
            $('body').addClass('remoteUpload');

        /* if ($('#remotefile').length && $('#remotefile').val() == $('#remotefile').data('oval'))
         {
         ssmsg.alert('You must enter a remote file to upload!')
         e.preventDefault();
         } else */

        e.preventDefault();
        $('.msg.error').hide();

        if ($('#remotefile').length === 0 && $("#start").find(".filename").length === 0)
            return ssmsg.alert(trn('please select upload file', 'Please select a file to upload.'));

        //$('.holder').trigger('click');
        var ownmail_entered = $('#ownemail').val();
        if ($('#recpemail').val())
        {
            if ($('#ownemail').attr('type') !== 'checkbox' && (!ownmail_entered || !is_email(ownmail_entered)))
            {
                $('#ownemail').focus();
                return ssmsg.alert(trn('please enter your from email', "Please enter your email address in the From box so the recipient knows who is sending the file"));
            }
        } else {
//                $('#ownemail').val() != $('#ownemail').data('oval') && 
            if (ownmail_entered && !is_email(ownmail_entered))
            {
                $('#ownemail').focus();
                return ssmsg.alert(trn('enter valid confirmation email', "Please enter a valid email to receive upload confirmation"));
            }
        }

        $start.find(".new_file:eq(0)").remove();
        var files = new Array();
        $("span.filename").each(function () {
            files.push($(this).text());
        });

        var progress_url = $("#start").find('form').find('input[name=PROGRESS_URL]').val();

        if (uploadformsubmit(progress_url, max_upload_size)) {
            $start.find("form").submit();
        }
    });

    /**
     * New file event.
     */

    // Various silly IE patches..
    if (navigator.userAgent.match(/(msie) ([\w.]+)/i))
    {
        // IE suspends timeouts until after the file dialog closes
        $start.on('click', '.new_file', function () {
            setTimeout(function () {
                $start.find('.new_file').trigger('change');
            }, 0);
        });

        $(document).click(function () {
            $('.button').css('border', 'none');
        });
    }

    $start.on('change', '.new_file', function () {

        if (window.FileList && this.files && this.files.length > 1) {

            for (var i = 0; i < this.files.length; i++) {
                var name = this.files[i].name;
                if (select_handler.has(name))
                    return ssmsg.alert(UPLOAD_ERROR_MSG_DUPLICATE.replace('o:filename:o', name));
            }

            for (var i = 0; i < this.files.length; i++) {
                var name = this.files[i].name;
                var size = this.files[i].size ? this.files[i].size : 0;

                if (size > max_upload_size) {
                    ssmsg.alert(UPLOAD_ERROR_MSG_TOO_BIG.replace("o:filename:o", name));
                    continue;
                }

                var filename = shorten_name(name, 35);

                if (!select_handler.add(name)) {
                    ssmsg.alert(trn('error adding %file, retry', 'An error occurred adding: o:name:o. Please try again.').replace('o:name:o', name));
                }

                var t = i ? null : $(this).hide().removeClass("new_file");

                var $clone = $("<tr/>")
                        .addClass("hoverable")
                        .data('full', $(this).val())
                        .html(slot.replace("o:filename:o", filename)).attr("title", name);


                $clone.find('a.remove').remove();

                if (t !== null)
                    $clone.append(t);

                $clone.insertAfter($start.find("tr.hoverable, tr.droid").last());
            }

            $('.add_more').html('<input type="file" title="' + trn('Add more files', 'Add more files') + '" name="upload_file[]" size="1" class="new_file" /> <label>' + trn('Add more files', 'Add more files') + '</label>');
            checkMulti();

            return;
        }

        if ($(this).val() === '')
            return;

        var filename = shorten_name($(this).val(), 35);
        var fullname = shorten_name($(this).val(), false);

        if (select_handler.count() >= 20)
            ssmsg.alert(trn('maximum files added', 'You have added the maximum amount of files'));
        else if (select_handler.add($(this).val()))
        {
            var t = $(this).hide().removeClass("new_file");

            var $clone = $("<tr/>")
                    .addClass("hoverable")
                    .data('full', $(this).val())
                    .html(slot.replace("o:filename:o", filename)).attr("title", fullname);

            $clone
                    .append(t)
                    .insertAfter($start.find("tr.hoverable, tr.droid").last());

            $('.add_more').html('<input type="file" title="' + trn('Add more files', 'Add more files') + '" name="upload_file[]" size="1" class="new_file" /> <label>' + trn('Add more files', 'Add more files') + '</label>');
        } else if ($.browser.msie) { /* IE get called only when focus is lost, and then cause the alert to pop twice */
        } else
            ssmsg.alert(trn('file was already selected', 'File was already selected'));
    });

    select_handler = new function () {
        var self = this;
        this.files = Array();

        this.count = function () {
            return self.files.length;
        }

        this.add = function (filename) {
            if (!in_array(filename, self.files)) {
                self.files.push(filename);
                return true;
            }
            return false;
        };

        this.has = function (filename) {
            return in_array(filename, self.files);
        };

        this.remove = function (filename) {
            var tmpArray = [];

            for (var i = 0; i < self.files.length; i++) {
                if (self.files[i] !== filename)
                    tmpArray.push(self.files[i]);
            }
            self.files = tmpArray;
        };

        $start.on("click", "a.remove", function () {
            var $tr = $(this).closest('.hoverable');
            var uid = $tr.data('uid');
            var filename = $tr.data('full');

            self.remove(filename);

            if (uid !== undefined && objUpload)
                objUpload.remove(uid);

            $tr.fadeOut('slow', function () {
                $tr.remove();
            });

            if ($('.new_file').length === 0)
                $('.add_more').append('<input type="file" id="newUpload" name="upload_file[]" size="1" class="new_file" />');
        });

    };

    var errorcounter = 0;
    var size_error = false;

    function uploadformsubmit(progressURL, upload_limit)
    {
        if ($("#remotefile").length === 1)
        {
            //|| $('#remotefile').val() == $('#remotefile').data('oval')
            if ($("#remotefile").val() === '')
                return ssmsg.alert('Please enter a valid URL');
        } else
        {
            if ($("#start").find(".filename").length === 0)
                return ssmsg.alert('Please select a file to upload');
        }

//        $('#start').find(".select .click_to_add").each(function ()
//        {
//            if ((typeof ($(this).data("first")) === "undefined" || !$(this).data("first") || $(this).val() == $(this).data('oval')))
//            {
//                $(this).val('');
//            }
//        });

//        if ($('#recpemail').val() == $('#recpemail').data('oval'))
//            $('#recpemail').val('');
//
//        if ($('#ownemail').val() == $('#ownemail').data('oval'))
//            $('#ownemail').val('');

        $('.msg.error,.no-other-versions,.upload_complete').hide();

        $("#start").find(".new_file:eq(0)").remove();
        $("#blog_headline").remove();

        if ($("#start").find('form').find('input[name=is_drag]').length) {
            $('#uploading_progress').remove();
            if (!$('#user_file_versions_page').length)
                $('#progress_bar').appendTo('#content');
            $('#progress_bar').show();

            $('body').attr('id', 'uploading_page');
            objUpload.uploadNext();

            return false;
        }

        $('body').attr('id', 'uploading_page');
        // don't pass limit if more than 1 file selected
        if (upload_limit && $("#start").find(".filename").length === 1)
            progressURL += '&limit=' + upload_limit;
        else
            progressURL += '&limit=0';

        if ($.browser.msie && parseInt($.browser.version) <= 7)
            $('#uploading_progress').width(800); // fix for IE7

        window.load_progress = function (progressURL)
        {
            var targetForm = $('#frm_progressbar');

            $(targetForm).attr('src', progressURL);
            $(targetForm).load(function () {
                $('#div_progressloading').hide();
                $(targetForm).show();
            });
        };

        // must load iframe with timeout or else it doesn't load
        setTimeout("load_progress('" + progressURL + "')", 1000);

        return true;
    }

    $(document).on('click', '#uploading_page a', function (e) {
        if (!e)
            var e = window.event;

        var rightclick = false;
        if (e.which)
            rightclick = (e.which === 3);
        else
        {
            if (e.button)
                rightclick = (e.button === 2);
        }
        if (rightclick)
            return true;

        var targ;
        if (e.target)
            targ = e.target;
        else
        {
            if (e.srcElement)
                targ = e.srcElement;
        }
        if (targ.nodeType === 3) // defeat Safari bug
            targ = targ.parentNode;
        if (!targ)
            return true;

        if (targ.id !== 'cancel_button' && ((targ.tagName === 'A' && targ.target !== '_blank') || targ.tagName === 'INPUT'))
            return confirm('Leaving this page will terminate the upload. Are you sure you want to continue?');

        return true;
    });

    $('#cancel_button').on('click', function (e) {
        e.preventDefault();
        var url = $('body').hasClass('remoteUpload') ? '/remoteupload.html' : '/';
        ssmsg.confirm({
            msg: trn('cancel this upload?', 'Are you sure you want to cancel this upload?'),
            focus: 'cancel',
            labels: {
                ok: trn('btn cancel upload', 'Cancel Upload'),
                cancel: trn('btn continue', 'Continue')
            },
            buttons: {
                ok: '<button class="sbtn caution" />',
                cancel: '<button class="sbtn" />'
            }
        }, function () {
            document.location.href = url + (url.indexOf('?') === -1 ? '?' : '&') + 'err=2';
        });
    });
});


/* legacy */
function cancelupload(url, sid)
{
    ssmsg.confirm({
        msg: trn('cancel this upload?', 'Are you sure you want to cancel this upload?'),
        focus: 'cancel',
        labels: {
            ok: trn('btn cancel upload', 'Cancel Upload'),
            cancel: trn('btn continue', 'Continue')
        },
        buttons: {
            ok: '<button class="sbtn caution" aria-label="' + trn('stop upload, ariahint', 'Stop the upload') + '" />',
            cancel: '<button class="sbtn" />'
        }
    }, function () {
        document.location.href = url + "?err=2&s=" + sid;
    });
}
