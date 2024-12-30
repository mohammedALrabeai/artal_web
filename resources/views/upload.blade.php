<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File to Amazon S3</title>
</head>
<body>
    <h1>Upload File to Amazon S3</h1>
    
    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
        <p>File URL: <a href="{{ session('file_url') }}" target="_blank">{{ session('file_url') }}</a></p>
    @endif

    @if ($errors->any())
        <ul style="color: red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('upload.file') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div>
            <label for="file">Choose file:</label>
            <input type="file" name="file" id="file" required>
        </div>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
