<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "utenti".
 *
 * @property integer $id
 * @property string $user_id
 * @property integer $ruolo
 * @property string $nome
 * @property string $cognome
 * @property string $email
 * @property integer $id_ufficio_gestore
 * @property integer $modified_by 
 * @property integer $abilitato 
 * @property integer $tipo
 * @property boolean $arpaauth 
 * @property string $password
 *
 * @property UfficiGestori $idUfficioGestore
 */
class Utenti extends \yii\db\ActiveRecord
{
    public $password_repeat; 

    public static $ruoli_utenti = [
        0 => 'Not Set',
        1 => 'Super User',
        2 => 'Amministratore',
        3 => 'Operatore Contabilità',
        4 => 'Visualizzazione',
    ];
    const RUOLO_SUPERUSER = 1;
    const RUOLO_ADMINISTRATOR = 2;
    const RUOLO_OPERATORE = 3;
    const RUOLO_LETTURA = 4;

    const SCENARIO_APPLICATIVO = 'applicativo';
    const SCENARIO_FRONTEND = 'frontend';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'utenti';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'ruolo', 'tipo'], 'required', 'message' => '{attribute} è un campo obbligatorio'],
            [['password', 'password_repeat', 'id_ufficio_gestore'], 'required', 'on' => self::SCENARIO_APPLICATIVO, 'message' => '{attribute} è un campo obbligatorio'],
            [['nome', 'cognome', 'email'], 'required', 'on' => self::SCENARIO_FRONTEND, 'message' => '{attribute} è un campo obbligatorio'],
            [['ruolo', 'id_ufficio_gestore', 'tipo', 'modified_by', 'abilitato'], 'integer'],
            [['arpaauth'], 'boolean'],
            [['user_id'], 'string', 'max' => 100],
            [['nome', 'cognome', 'password'], 'string', 'max' => 255],
            [['email'], 'email', 'message' => 'Il campo Email deve essere di formato e-mail'],
            [['user_id', 'email'], 'unique'],
            ['tipo', 'in', 'range' => [1, 2]],//check if tipo is 1: utenza di front-end 2: utenza applicativa 
            ['password_repeat', 'compare', 'compareAttribute'=>'password', 'message'=>"Le password non coincidono" ],
            [['id_ufficio_gestore'], 'exist', 'skipOnError' => true, 'targetClass' => UfficiGestori::className(), 'targetAttribute' => ['id_ufficio_gestore' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $labalsBase = [
            'id' => 'ID',
            'user_id' => 'Nome Utente',
            'ruolo' => 'Ruolo',
            'password' => 'Password',
            'password_repeat' => 'Ripeti Password',
            'id_ufficio_gestore' => 'Ufficio Gestore',
            'modified_by' => 'Modificato da',
            'abilitato' => 'Abilitato',
        ];
        if($this->scenario == self::SCENARIO_APPLICATIVO){
            $arrLabelsScenario = [
                'nome' => 'Nome Referente',
                'cognome' => 'Cognome Referente',
                'email' => 'Email Referente',
            ];
        }
        else{
            $arrLabelsScenario = [
                'nome' => 'Nome',
                'cognome' => 'Cognome',
                'email' => 'Email',
                'arpaauth' => 'Autenticazione ARPA'
            ];
        }
        $arrLabels = array_merge($labalsBase, $arrLabelsScenario);
        return $arrLabels;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIdUfficioGestore()
    {
        return $this->hasOne(UfficiGestori::className(), ['id' => 'id_ufficio_gestore']);
    }

    /**
    *   @return \app\models\UfficiGestory list
    */
    public function UfficiGestoriList()
    {
        $ufficiret =[];
        $uffici = UfficiGestori::find()->orderBy('nome')->all();
        foreach($uffici as $ufficio){
            $ufficiret[$ufficio->id] = $ufficio->nome;
        }
        return $ufficiret;
    }
    /**
    *   Method to enable delete function for a Usesr
    *   @return boolean
    */
    public function CanBeDeleted()
    {
        $log = LogChiamata::find()->where(['user_id' => $this->id])->one();
        return !$log;
    }
    /**
    *   This method is called at the beginning of deleting a record
    *   @return boolean
    */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        if(!$this->CanBeDeleted()){
            $this->addError(null, "Impossibile eliminare l'utente");
            return false;
        }
        return true;
    }
    public function beforeValidate()
    {
        if (empty($this->tipo)){
            $this->tipo = 2; //di default utenza applicativa
        }
        if (!parent::beforeValidate()) {
            return false;
        }
        return true;
    }
    /**
    *   This method is called at the beginning of inserting or updating a record
    *   @param \yii\db\ActiveRecord $utente
    *   @return boolean
    */
    public function beforeSave($utente)
    {
        if (!parent::beforeSave($utente)) {
            return false;
        }
        $this->encryptPassword();
        return true;
    }
    public function encryptPassword()
    {
        $this->password = User::encrypting("{$this->user_id};{$this->password}");
    }
    /**
    *
    *   @return array ['usr' 'passwd']
    */
    public function getCredenziali()
    {
        $credenziali = ['user_id' => null, 'password' => null];
        if(isset($this->idUfficioGestore) && isset($this->idUfficioGestore->credenzialiWs)){
            $credenzialiWS = $this->idUfficioGestore->credenzialiWs;
            $credenziali['user_id'] = $credenzialiWS->user_id;
            $credenziali['password'] = $credenzialiWS->dencryptPassword();
        }
        return $credenziali;
    }
    /**
    * Get Current user logged 
    *
    * @return app/models/Utenti
    */
    public static function CurrentUtente()
    {
        $userid = \Yii::$app->user->identity->id;
        return Utenti::findOne($userid);
    }
    /**
    * Get Current user's id-Group logged 
    *
    * @return integer
    */
    public static function CurrentUtenteGroup()
    {
        $utente= self::CurrentUtente();
        $group = $utente ? $utente->id_ufficio_gestore : null;
        return $group;
    }
    
    /**
    *   @return \app\models\Utenti list
    */
    public function UtentiList()
    {
        $utentiret =[];
        $utenti = Utenti::find()->orderBy('user_id')->all();
        foreach($utenti as $utente){
            $utentiret[$utente->id] = $utente->user_id;
        }
        return $utentiret;
    }
}
