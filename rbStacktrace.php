<?php
    /*
     * this is the template file for a pretty stack trace.
     */

    $errFile = $e->getFile();

    if (substr($errFile, 0, strlen($realpath)) == $realpath) {
        $errFile = 'AppRoot:' . substr($errFile, strlen($realpath));
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Error: <?php echo prints($e->getMessage()); ?></title>
<style type="text/css">
    HTML {
        position: absolute;
    }
    BODY {
        font-family: "Helvetica", "Verdana", "Arial", sans-serif;
        background-color: #333;
    }

    #frame {
        width: 80%;
        margin: 0 auto;
    }

    #head, #trace {
        background-color: #FFF;
        padding: 16px;
        margin-bottom: 16px;
    }

    .errfileline {
        font-family: "Courier new";
        font-size: 0.7em;
    }

    .trace_line {
        /* border: 1px solid black; */
        background-color: #f0f0f0;
        margin: 4px 4px 16px 4px;
        font-size: 0.8em;
    }

    .trace_line_file {
        padding: 4px;
        background-color: #336699;
        color: #fff;
    }

    .trace_line_function {
        padding: 8px;
        color: #666;
    }

    OL #trace_list {
        list-style-type: decimal-leading-zero;
    }

</style>
</head>
<body>
<div id="frame">
<div id="head">
<h1>Error: <?php prints($e->getMessage()); ?></h1>
<div class="errfileline">in file: <?php prints($errFile); ?><b>[<?php prints($e->getLine()); ?>]</b></div>
</div>
<div id="trace">
<h3>Trace</h3>

<ol id="trace_list">
<?php
    foreach ($e->getTrace() as $num => $trace) {
        
        if (empty($trace['file'])) {
            $trace['file'] = '{internal function}';

        } else if (substr($trace['file'], 0, strlen($realpath)) == $realpath) {
            // file is one of the apps'
            $trace['file'] = 'AppRoot:' . substr($trace['file'], strlen($realpath));
        }

        // no line number (internal functions)
        if (empty($trace['line']))
            $trace['line'] = '*';

        // no arguments
        if (empty($trace['args']))
            $trace['args'] = 'none';

        // prettify the arguments:
        // for arrays: the number of elements it contains (too much clutter otherwise)
        // for objects: the instances class name
        // otherwise the stringified representation
        $args = array();
        if (!empty($trace['args']) && is_array($trace['args'])) {
            foreach ($trace['args'] as $arg) {
                if (is_array($arg))
                    $args[] = 'Array(' . count($arg) . ')';
                elseif (is_object($arg))
                    $args[] = 'Object:"' . get_class($arg) . '"';
                else
                    $args[] = '\'' . (string)$arg . '\'';
            }

            $args = implode(', ', $args);
        }
?>

        <li>
            <div class="trace_line">
                <div class="trace_line_file"    >File: <?php prints($trace['file']); ?><b>[<?php prints($trace['line']); ?>]</b></div>
                <div class="trace_line_function">Func: <?php prints($trace['function']); ?> (<?php prints($args); ?>)</div>
            </div>
        </li>
<?php
    }
?>
    </ol>
    </div>
    </div>
</body>
</html>