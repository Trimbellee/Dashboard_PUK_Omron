<!DOCTYPE html>
<html>

<head>

<title>{{$file->nama_file}}</title>

<style>

body{
margin:0;
font-family:Arial;
background:#111;
color:white;
}

.topbar{
display:flex;
justify-content:space-between;
align-items:center;
padding:10px 20px;
background:#222;
}

.viewer{
height:calc(100vh - 50px);
display:flex;
justify-content:center;
align-items:center;
}

img{
max-width:100%;
max-height:100%;
}

iframe{
width:100%;
height:100%;
border:none;
}

</style>

</head>

<body>

<div class="topbar">

<div>
{{$file->nama_file}}
</div>

<a href="{{asset('storage/'.$file->path)}}"
download
style="color:#4da3ff">
Download
</a>

</div>

<div class="viewer">

@php
$ext = strtolower(pathinfo($file->nama_file, PATHINFO_EXTENSION));
@endphp

@if(in_array($ext,['jpg','jpeg','png','gif','webp']))

<img src="{{asset('storage/'.$file->path)}}">

@else

<iframe src="{{asset('storage/'.$file->path)}}"></iframe>

@endif

</div>

</body>

</html>