/**
 * Created by mardraze on 30.03.15.
 */
window.MardrazeUploader = window.MardrazeUploader || {
    'make' : function(id_prefix, options){
        var opt = $.extend({
            runtimes: "html5",
            browse_button : id_prefix+'_pickfiles', // you can pass in id...
            container: document.getElementById(id_prefix+'_container'), // ... or DOM Element itself
            url: "",
            chunk_size: '1024kb',
            filters : {
                max_file_size : '10mb',
                mime_types: [
                    {title : "Image files", extensions : "jpg,gif,png"},
                    {title : "Zip files", extensions : "zip"}
                ]
            }
        }, options);

        opt.init = $.extend({
            PostInit: function() {
                document.getElementById(id_prefix+'_filelist').innerHTML = '';

                document.getElementById(id_prefix+'_uploadfiles').onclick = function() {
                    uploader.start();
                    return false;
                };
            },

            BeforeFilesAdded: function(up, files) {

            },
            FilesAdded: function(up, files) {
                opt.init.BeforeFilesAdded(up, files);
                plupload.each(files, function(file) {
                    document.getElementById(id_prefix+'_filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
                });
            },

            UploadProgress: function(up, file) {
                document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
            },

            Error: function(up, err) {
                console.log("Error #" + err.code + ": " + err.message);
            },

            UploadFile: function(){},
            BeforeUpload: function(){},
            FileUploaded: function(upldr, file, object){
                console.log('FileUploaded', upldr, file, object);
            }
        }, options.init || {});

        var uploader = new plupload.Uploader(opt);

        uploader.init();
        return uploader;
    }
};