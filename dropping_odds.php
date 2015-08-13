#!/usr/local/bin/php
<?php
define ('ROOT', dirname(__FILE__).'/' );
require(ROOT.'inc/app.php');
require(ROOT.'inc/config.php');
require(ROOT.'inc/class.phpmailer.php');
require(ROOT.'inc/class.smtp.php');

class Parser extends App{
    
    private $config = array();
    private $db;
    private $odds = array();
    
    public function __construct( $config ){
        $this->config = $config;
        parent::__construct( $config );
    }

    public function get_odds(){
        // Ползем за HTML документом
        $get = $this->get('dropping_odds_browser.html');
        $noko = new nokogiri($get);
        $res = $noko->get("div[id=dropping_odds_content]")
                    ->get("div[class=box]")
                    ->get("table[class=table-main]")
                    ->toArray();

        // Обход строк интересующей таблицы с дропами
        foreach ($res[0]["tbody"][0]["tr"] as $tr) {
            // Если хедер с указанием группы - берем текущую группу
            if ($tr["th"]["class"] == "first-last") {
                $curr_header = $tr["th"]["a"][0]["#text"]." >> ".$tr["th"]["a"][1]["#text"][0]." >> ".$tr["th"]["a"][2]["#text"];
            }

            // Матчи
            if ($tr["class"] == "odd sr1") {
                $first = $tr["td"][3]["span"]["span"]["#text"];
                if($first == "")
                    $first = $tr["td"][3]["span"]["span"][0]["#text"]." -> ".$tr["td"][3]["span"]["strong"][0]["span"]["#text"];
                $x = $tr["td"][4]["span"]["span"]["#text"];
                if($x == "")
                    $x = $tr["td"][4]["span"]["span"][0]["#text"]." -> ".$tr["td"][4]["span"]["strong"][0]["span"]["#text"];
                $second = $tr["td"][5]["span"]["span"]["#text"];
                if($second == "")
                    $second = $tr["td"][5]["span"]["span"][0]["#text"]." -> ".$tr["td"][5]["span"]["strong"][0]["span"]["#text"];
                preg_match("/t([0-9]*)-/", $tr["td"][0]["class"], $a_dat);
                $date = gmdate("Y-m-d G:i:s", $a_dat[1]);
                $this->odds[]= array(
                    "header" => $curr_header,
                    "date" => $date,
                    "match" => $tr["td"][1]["a"]["#text"],
                    "drop" => str_replace('%', '', $tr["td"][2]["#text"]),
                    "1" => $first,
                    "X" => $x,
                    "2" => $second,
                    "b_s" => $tr["td"][6]["#text"]
                );
            }
        }
    }

    public function check_odds(){
        $this->odds = array_filter($this->odds, function($x) {
            $curdat = new DateTime();
            $curdat->setTimezone(new DateTimeZone('Etc/UTC'));
            $matchdat = new DateTime($x['date'], new DateTimeZone('Etc/UTC'));
            $diff = (strtotime($matchdat->format("Y-m-d h:i")) - strtotime($curdat->format("Y-m-d h:i")))/60;
            return ($x['drop'] <= -7 && $diff <= 150 and $diff >= 0);
        });
    }

    public function send_mail(){
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'html';
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth = true;
        $mail->Username = "sender@host.domain_zone";
        $mail->Password = "password";
        $mail->setFrom('sender@host.domain_zone', 'Dropping Odds Mailer');
        $mail->addReplyTo('sender@host.domain_zone', 'Dropping Odds Mailer');
        $mail->addAddress('receiver@host.domain_zone', 'Mr Receiver');
        $mail->Subject = 'Dropping Odds (Test data, conditions has been changed due to testing period)';

        $table_style = 'border-collapse: collapse; border: 1px solid gray; padding: 5px;';
        $th_style = 'border: 1px solid gray; color:white; background-color:green;';
        $td_style = 'border: 1px solid gray;';

        $msg = '
            <table>
                <tr style="'.$table_style.'">
                    <th style="'.$th_style.'">Date (UTC)</th>
                    <th style="'.$th_style.'">League</th>
                    <th style="'.$th_style.'">Game</th>
                    <th style="'.$th_style.'">Drop</th>
                    <th style="'.$th_style.'">1</th>
                    <th style="'.$th_style.'">X</th>
                    <th style="'.$th_style.'">2</th>
                    <th style="'.$th_style.'">b_s</th>
                </tr>
        ';
        foreach ($this->odds as $odd) {
            $msg .= '<tr>';
            $msg .= '<td style="'.$td_style.'">'.$odd['date'].'</td>';
            $msg .= '<td style="'.$td_style.'">'.$odd['header'].'</td>';
            $msg .= '<td style="'.$td_style.'">'.$odd['match'].'</td>';
            $msg .= '<td style="'.$td_style.'">'.$odd['drop'].'</td>';
            $msg .= '<td style="'.$td_style.'">'.$odd['1'].'</td>';
            $msg .= '<td style="'.$td_style.'">'.$odd['X'].'</td>';
            $msg .= '<td style="'.$td_style.'">'.$odd['2'].'</td>';
            $msg .= '<td style="'.$td_style.'">'.$odd['b_s'].'</td>';
            $msg .= '</tr>';
        }
        $msg .= '
            </table>
        ';

        echo $msg;
        $mail->msgHTML($msg);

        if(!empty($this->odds)) {
            if (!$mail->send()) {
                echo "Mailer Error: " . $mail->ErrorInfo;
            } else {
                echo "Message sent!";
            }
        }
    }

    public function run(){
        $this->get_odds();
        $this->check_odds();
        $this->send_mail();
    }

}

try{
    $parser = new Parser( $config );
    $parser->run();
} catch( Exception $e ) {
    die( 'Exception: '.$e->getMessage() );
}
