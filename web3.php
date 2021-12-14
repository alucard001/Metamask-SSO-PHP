<?php
use kornrunner\Keccak;
use Elliptic\EC;

require_once('vendor/autoload.php');

// https://stackoverflow.com/a/14890525/1802483
$data = json_decode(file_get_contents("php://input"), true);

if( isset( $data['signature'] ) ) {

    $signature = $data['signature'];
    $address = $data['address'];
    $message = $data['message'];

    $messageLength = strlen($message);
    $hash = Keccak::hash("\x19Ethereum Signed Message:\n{$messageLength}{$message}", 256);

    $sign = [
        "r" => substr($signature, 2, 64),
        "s" => substr($signature, 66, 64)
    ];

    $recId  = ord(hex2bin(substr($signature, 130, 2))) - 27;
    if ($recId != ($recId & 1)) {
        return false;
    }

    $publicKey = (new EC('secp256k1'))->recoverPubKey($hash, $sign, $recId);
    $pubkey_addr = "0x" . substr(Keccak::hash(substr(hex2bin($publicKey->encode("hex")), 1), 256), 24);

    if( $pubkey_addr === strtolower( $address ) ) {
        print json_encode(['success' => true]);
    }

    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web3</title>
    <script src="https://cdn.jsdelivr.net/npm/web3@latest/dist/web3.min.js"></script>
</head>
<body>
<script>

loginweb3();

async function loginweb3(){
    if (! window.ethereum) {
        console.error('MetaMask not detected. Please try again from a MetaMask enabled browser.')
    }

    const web3 = new Web3(window.ethereum);

    const message = [
        "I have read and accept the terms and conditions (https://example.org/tos) of this app.",
        "Please sign me in!"
    ].join("\n")

    const address = (await web3.eth.requestAccounts())[0];
    const signature = await web3.eth.personal.sign(message, address);

    const data = {
        'message': message,
        'address': address,
        'signature': signature,
    };

    // https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch
    const response = await fetch('web3.php', {
        method: 'POST', // *GET, POST, PUT, DELETE, etc.
        mode: 'cors', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            // 'Content-Type': 'application/json'
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: JSON.stringify(data) // body data type must match "Content-Type" header
    });

    let resp = await response.json(); // parses JSON response into native JavaScript objects

    if(resp.success === true) {
        console.log("It works");
    }else{
        console.log("It fails");
    }
};
</script>
</body>
</html>