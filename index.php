<?php
    header('Content-Type: application/xml; charset=utf-8');
    echo
        "<?xml version='1.0' encoding='UTF-8'?>",
        "<brAWbServiceResponse>",
            "<responseStatus>",
                "Your API Key is missing. Please, check your request.",
            "</responseStatus>",
        "</brAWbServiceResponse>";
?>
