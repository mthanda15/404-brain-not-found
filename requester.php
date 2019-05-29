<?php
/**
 * @file requester.php
 * Sample client form to submit and test a program.
 */
// Show errors/warnings
error_reporting(E_ALL);
ini_set('display_errors', '1');
// The test program
$source=<<<EOF
//
//  main.c
//  Sheet14FAC
//
//  Created by Jonah Hooper on 2013/04/10.
//  Copyright (c) 2013 Jonah Hooper. All rights reserved.
//

#include <stdio.h>
#include <math.h>
#include <stdlib.h>
#define ERROR_TO_BIG "Must be between 0 and 1"
char* getBinaryString (float num)
{
    if (abs(num) >= 1)
    {
        int trunc = (int) num;
        int len = 0;
        while ((float)trunc != num)
        {
            num *= 2;
            trunc = (int) num;
            //printf("num: %f, tr: %d\n",num,trunc);
            len++;
        }
        char* ret = calloc(sizeof(char), len + 2);
        ret[0] = '0';
        ret[1] = '.';
        trunc <<= 1;
        int i = 0;
        for (i = len+2 ; i >= 2; i--)
        {
            if ((trunc & 0x1) == 1)
                ret[i] = '1';
            else ret[i] = '0';
            trunc >>= 1;
        }
        return ret;
    } return ERROR_TO_BIG;
    
}
float sigmaxpown (float x,float total, int n)
{
    float add = powf(x, n);
    printf("Total: %f n: %d\n",total,n);
    total += add;
    n--;
    if (n == 0)
    {
        total++;
        printf("Total: %f n: %d\n",total,n);
        return total;
    }
    else
        return sigmaxpown(x,add,n);
}
float sigmaPowUntilError (float x0, float error) {
    float xl;
    float x = x0;
    float ret = 0;
    int count = 0;
    do {
        xl = x;
        x = powf(x0, count);
        printf("%f\n",ret);
        printf("%f %f\n",x, xl);
        ret+= x;
        count++;
    }while (fabs(xl - x) >= error);
    return ret;
}
int main()
{

    float bf;
    float error;
    // insert code here...
    /*
    printf("Enter float to convert to binary\n");
    scanf("%f",&bf);
    printf("%s\n",getBinaryString(bf));
     */
    /*
    int n;
    printf("An float and integer\n");
    scanf("%f %d",&bf, &n);
    printf("Final %f\n", sigmaxpown(bf, 0.f, n));
     */
    printf ("Input number and error\n");
    scanf("%f %f",&bf,&error);
    printf("%f",sigmaPowUntilError(bf, error));
    return 0;
}

EOF;
// Always base64_encode the source
$source = base64_encode($source);
// languageid, source, input, output and timelimit
$data = array("language" => 5,"source"=>$source,
        "input"=> 'test', "output" => 'Hello', "timelimit" =>3);
// json_encode the object to send to the marker
$data_string = json_encode($data);                                                                                   

// Post the data to the marker
$options = array(
  'http' => array(
    'method'  => 'POST',
    'content' => $data_string ,
    'header'=>  "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n"
    )
);
$url = "http://127.0.0.1/mark/mark.php";

$context  = stream_context_create( $options );
$result = file_get_contents( $url, false, $context );
$response = json_decode( $result );

// Show the response
var_dump($response);

?>
