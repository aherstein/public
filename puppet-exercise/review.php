<?php
error_reporting(E_ERROR);
$AUTH_TOKEN = "7a633d40e098293bcf8ed8f990ded3e8556c9623";

// Function to make a cURL command to a given URL.
function get($url)
{
    $options = array(
        CURLOPT_URL            => $url,
        CURLOPT_HEADER         => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 400,
        CURLOPT_USERAGENT => "Adam-Herstein-Review-Script"
//      CURLOPT_USERPWD => "$AUTH_TOKEN:x-oauth-basic",
//      CURLOPT_HTTPAUTH => CURLAUTH_BASIC
    );

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    if (!$result = curl_exec($ch))
    {
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    
    return json_decode($result, true);
}



function isInteresting($item)
{
    $files = get($item['url'] . "/files");

    foreach ($files as $file)
    {
        if (strpos($file['filename'], "spec" === 0)) return false; // Does not contain changes to any files in the "spec/" directory
        if (strpos($file['filename'], "Gemfile") !== false) return true; // Any change to the file Gemfile
        if (strpos($file['filename'], ".gemspec") !== false) return true; // Any change to the file .gemspec

        // Parse through added and removed lines
        $patch = explode("\n", $file['patch']);
        foreach($patch as $line)
        {
            if (substr($line, 0, 1) == "+" || substr($line, 0, 1) == "-")
            {
                $words = explode(" ", $line);
                foreach($words as $word)
                {
                    if
                    (
                        $word == "/dev/null" ||
                        $word == "raise" ||
                        $word == ".write" ||
                        $word == "%x" ||
                        $word == "exec"
                    )
                    return true;
                }
            }
        }
    }

    return false;
}

$repo = $argv[1]; // Get repo name from parameters

$pullRequests = get("https://api.github.com/repos/$repo/pulls?state=open"); // Get list of pulls

foreach ($pullRequests as $item)
{
    echo $item['url'];
    echo isInteresting($item) ? " Interesting\n" : " Not Interesting\n";
}
