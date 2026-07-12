<!DOCTYPE html>
<html>
<head>
    <title>TUS Upload</title>
    <script src="https://cdn.jsdelivr.net/npm/tus-js-client@latest/dist/tus.min.js"></script>
</head>
<body>

<h2>Upload File</h2>

<input type="file" id="fileInput">

<script>

document.getElementById("fileInput").addEventListener("change", function(e) {

    var file = e.target.files[0];

    var upload = new tus.Upload(file, {

        endpoint: "uploadtus.php",

        retryDelays: [0, 3000, 5000, 10000],

        metadata: {
            filename: file.name,
            filetype: file.type
        },

        onError: function(error) {
            console.log("Upload failed:", error);
        },

        onProgress: function(bytesUploaded, bytesTotal) {
            var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2);
            console.log(bytesUploaded, bytesTotal, percentage + "%");
        },

        onSuccess: function() {
            console.log("Upload finished:", upload.url);
        }

    });

    upload.start();

});

</script>

</body>
</html>