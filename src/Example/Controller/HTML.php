<?php

class Example_Controller_HTML extends Mdogo_Controller
{
  /**
   * @var array
   */
  protected $examples;


  /**
   * Accepts request method, path
   *
   * @param string $method
   * @param string $path
   * @return void
   */
  public function bootstrap(&$method, &$path)
  {
    parent::bootstrap($method, $path);
    $this->examples = dir_get_files(__DIR__ .'/..', 'php');
  }


  /**
   * Prepares response object
   *
   * @throws MdogoNotFoundException
   * @return Mdogo_Response
   */
  public function respond()
  {
    $segments = explode('/', $this->path);
    if (isset($segments[1])) {
      $data = $this->process($segments[1]);
    }
    elseif (in_array($this->path, array('', 'example', 'example/'))) {
      $data = $this->index();
    }
    else {
      throw new MdogoNotFoundException();
    }
    $view = new Mdogo_View($this->getTemplate());

    $this->response->ttl = -1;
    $this->response->setBody($view->render($data), 'html');
    return $this->response;
  }


  /**
   * Prepares example data
   */
  protected function process($name)
  {
    $class = 'Example_'. $name;
    if (!class_exists($class)) {
      throw new MdogoNotFoundException();
    }
    $example = new $class();
    return array(
      'title'   => $name,
      'result'  => json_encode($example->run(), true),
      'source'  => file_get_contents($this->examples[$name])
    );
  }


  /**
   * Prepares example index view
   */
  protected function index()
  {
    $index = array_map(
      function ($example) {
        return array(
          'title' => $example,
          'url' => '//'. MDOGO_HOST .'/example/'. $example
        );
      },
      array_keys($this->examples)
    );
    return array('title' => 'Examples', 'index' => $index);
  }


  /**
   * Gets html template for example page
   *
   * @return string
   */
  public static function getTemplate()
  {
    return <<<'EOT'
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ title }}</title>
<link rel="shortcut icon" href="about:blank">
<link href="//cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.css" type="text/css" rel="stylesheet">
<style type="text/css">
body { font-family: Helvetica, sans-serif; color: #222; }
pre.prettyprint { border: 1px dotted #222; padding: 1em; margin-bottom: 2em; }
hr { border 0; border-top: 1px solid #222; }
a, a:visited { color: #222; }
</style>
</head>
<body onload="prettyPrint()">
<div style="width: 80%; margin: 1em auto; overflow: hidden;">

<h1>Mdogo {{ title }}</h1>
<hr>

<ol>{{#index}}
<li><a href="{{url}}">{{title}}</a></li>
{{/index}}</ol>

{{#source}}
<h2>Source</h2>
<pre class="prettyprint">
{{source}}
</pre>
<hr>
{{/source}}

{{#result}}
<h2>Result</h2>
<pre id="result" class="prettyprint"></pre>
<script type="text/javascript">
  !function () {
    var result = {{&result}};
    document.getElementById("result").innerHTML = JSON.stringify(result, undefined, 2);
  }();
</script>
<hr>
<a href="javascript:history.back();">Back to Index</a>
{{/result}}
<hr>

</div>
<script src="//cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.js" type="text/javascript"></script>
</body>
</html>
EOT;
  }
}
