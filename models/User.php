<?php

namespace app\models;

class User extends \yii\base\BaseObject implements \yii\web\IdentityInterface
{
    public $id;
    public $username;
    public $password;
    public $authKey;
    public $accessToken;
    public $ruolo;
    public $error;

    protected static function LoadUser($Utente)
    {
        $utente = new User();
        $utente->id = $Utente->id;
        $utente->username = $Utente->user_id;
        $utente->password = $Utente->password;
        $utente->ruolo = $Utente->ruolo;
        $utente->authKey = $Utente->user_id ."-". $Utente->id ."key";
        $utente->accessToken = $Utente->user_id ."-". $Utente->id ."token";
        return new static ($utente);
    }
    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        $Utente = Utenti::findOne($id);
        if(!$Utente){
            return null;
        }
        return self::LoadUser($Utente);
    }
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        //foreach (self::$users as $user) {
        //    if ($user['accessToken'] === $token) {
        //        return new static($user);
        //    }
        //}

        return null;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        $Utente = Utenti::find()->where(['user_id' => $username])->one();
        if(!$Utente){
            return new User();
        }
        return self::LoadUser($Utente);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
    * Logs in a user using the provided username and password.
    * @return bool whether the user is logged in successfully
    */
    public static function login($in_username, $password, $remember = 0)
    {   
        $utente = Utenti::find()->where(['user_id' => $in_username])->one();
        if(!$utente){
            //echo "Impossibile recuperare le credenziali, username inesistente {$in_username}, {$password}"; die();
            return "Impossibile recuperare le credenziali, username inesistente"; 
        }
        $password = self::encrypting("{$in_username};{$password}");
        if ($utente->password == $password) {
            if(\Yii::$app->user->login(self::LoadUser($utente), $remember)){
                return true;
            }
        }
        //else{
        //    $this->error = 'Impossibile recuperare le credenziali, password non corretta';
        //}
        //    echo "Impossibbile effettuare l'accesso, password non corretta {$in_username}, {$password}"; die();
        return "Impossibbile effettuare l'accesso, password non corretta.";
    }

    /**
    * Logout user session.
    * @return bool whether the user is logged in successfully
    */
    public static function logout()
    {
        \Yii::$app->user->logout();
        define('_USERLOGGEDID', null);
    }
    /**
	 * @return hash string.
	 */
	public static function encrypting($string="") 
    {
		$hash = \Yii::$app->params['User_Password_HashAlg'];
        //if ($hash=="md5")
        //    return md5($string);
        //if ($hash=="sha1")
        //    return sha1($string);
        //else
			return hash($hash,$string);
	}
    /**
    * Return id current user loggedin
    * @return integer Utenti->id
    */
    public static function getCurrentUserId()
    {
        return \yii::$app->user->id;
    }
    
    /**
    * User action auth
    *
    * @param string $controller nome del controller
    * @param string $action nome della action
    * @return boolean
    */
    public static function can($controller, $action = null, $group = null)
    {
        $group_owner = $group != null && Utenti::CurrentUtenteGroup() == $group;
        $is_superadmin = \Yii::$app->user->identity->ruolo == Utenti::RUOLO_SUPERUSER;
        $is_contabile = \Yii::$app->user->identity->ruolo == Utenti::RUOLO_CONTABILITA;
        $is_admin_ufficio = \Yii::$app->user->identity->ruolo == Utenti::RUOLO_ADMIN_UFFGEST;
        switch(strtolower($controller)){
            case 'clienti':
                //echo \Yii::$app->user->identity->ruolo .'=='. Utenti::RUOLO_ADMIN_UFFGEST .' && (( '. $group .' != null && . '. Utenti::CurrentUtenteGroup() .' == '. $group .') || '. strtolower($action) ."== 'index'))";die();
                //echo $is_admin_ufficio . '&&  ('. $group_owner .' || '. strtolower($action) ." == 'index')";
                $is_adimn_owner = $is_admin_ufficio &&  ($group_owner || strtolower($action) == 'index');
                return ($is_superadmin || $is_adimn_owner);
            case 'utenti':
                return $is_superadmin;
            default:
                return false;
        }
        
    }
}
