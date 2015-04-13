<?php

/**
 * Authentication API used by server to authenticate a user 
 * using his alternc login and password
 */
class Alternc_Api_Auth_Login implements Alternc_Api_Auth_Interface {

    private $db; // PDO object

    const ERR_INVALID_ARGUMENT = 1111201;

    /**
     * Constructor of the Login Api Auth
     *
     * @param $service an Alternc_Api_Service object
     * @return create the object
     */
    function __construct($service) {

        if (!($service instanceof Alternc_Api_Service))
            throw new \Exception("Invalid argument (service)", ERR_INVALID_ARGUMENT);

        $this->db = $service->getDb();
    }

    /**
     * Authenticate a user
     *
     * @param $options options, depending on the auth scheme, including uid for setuid users
     *   here, login is the AlternC username, and password is the password for this username.
     * @return an Alternc_Api_Token
     */
    function auth($options) {

        if (!isset($options["login"]) || !is_string($options["login"])) {
            throw new \Exception("Missing required parameter login", self::ERR_INVALID_ARGUMENT);
        }
        if (!isset($options["password"]) || !is_string($options["password"])) {
            throw new \Exception("Missing required parameter password", self::ERR_INVALID_ARGUMENT);
        }

        if (!preg_match("#^[0-9a-zA-Z-]{1,32}$#", $options["login"])) { // FIXME : normalize this on AlternC !!!
            throw new \Exception("Invalid login", self::ERR_INVALID_LOGIN);
        }

        $stmt = $db->query("SELECT m.enabled,m.uid,m.login,m.su FROM membres m WHERE m.login=? AND m.password=?;", array($options["login"], $options["password"]), PDO::FETCH_CLASS);
        $me = $stmt->fetch();
        if (!$me)
            return new Alternc_Api_Response(array("code" => ERR_INVALID_AUTH, "message" => "Invalid login or password"));
        if (!$me->enabled)
            return new Alternc_Api_Response(array("code" => ERR_DISABLED_ACCOUNT, "message" => "Account is disabled"));

        return Alternc_Api_Token::tokenGenerate(
                        array("uid" => $me->uid, "isAdmin" => ($me->su != 0)), $this->db
        );
    }

    /**
     * Api Documentation
     * @return array("fields" => array("fields to send, required or not"), "description" => "description of this auth")
     */
    function documentation() {
        return array("fields" => array("login" => "AlternC user account", "password" => "AlternC's user password stored in membres table."),
            "description" => "Authenticate against an AlternC user and password, the same as for the control panel"
        );
    }

}

// class Alternc_Api_Auth_Login

