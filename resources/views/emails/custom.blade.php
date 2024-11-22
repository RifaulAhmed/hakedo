<!-- resources/views/emails/custom.blade.php -->

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>{{$data['subject']}}</title>
</head>

<body>
    <div class="email_body_style">
  
      {!! $data['body'] !!}

      <p class="footer_email">
        &copy; <?php echo date ('Y'); ?>  {{$data['company_name']}}. {{ __('translate.All rights reserved') }}</p>
    </div>
</body>

</html>