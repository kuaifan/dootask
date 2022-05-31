<?php
/**
 * @Description :
 *
 * @Date        : 2019-03-14 13:20
 * @Author      : hmy940118@gmail.com
 */

namespace App\Module\AgoraIO;


class AgoraTokenGenerator
{
    const AttendeePrivileges = array(
        AccessToken::Privileges["kJoinChannel"] => 0,
        AccessToken::Privileges["kPublishAudioStream"] => 0,
        AccessToken::Privileges["kPublishVideoStream"] => 0,
        AccessToken::Privileges["kPublishDataStream"] => 0
    );


    const PublisherPrivileges = array(
        AccessToken::Privileges["kJoinChannel"] => 0,
        AccessToken::Privileges["kPublishAudioStream"] => 0,
        AccessToken::Privileges["kPublishVideoStream"] => 0,
        AccessToken::Privileges["kPublishDataStream"] => 0,
        AccessToken::Privileges["kPublishAudioCdn"] => 0,
        AccessToken::Privileges["kPublishVideoCdn"] => 0,
        AccessToken::Privileges["kInvitePublishAudioStream"] => 0,
        AccessToken::Privileges["kInvitePublishVideoStream"] => 0,
        AccessToken::Privileges["kInvitePublishDataStream"] => 0
    );

    const SubscriberPrivileges = array(
        AccessToken::Privileges["kJoinChannel"] => 0,
        AccessToken::Privileges["kRequestPublishAudioStream"] => 0,
        AccessToken::Privileges["kRequestPublishVideoStream"] => 0,
        AccessToken::Privileges["kRequestPublishDataStream"] => 0
    );

    const AdminPrivileges = array(
        AccessToken::Privileges["kJoinChannel"] => 0,
        AccessToken::Privileges["kPublishAudioStream"] => 0,
        AccessToken::Privileges["kPublishVideoStream"] => 0,
        AccessToken::Privileges["kPublishDataStream"] => 0,
        AccessToken::Privileges["kAdministrateChannel"] => 0
    );
    const Role = array(
        "kRoleAttendee" => 0,  // for communication
        "kRolePublisher" => 1, // for live broadcast
        "kRoleSubscriber" => 2,  // for live broadcast
        "kRoleAdmin" => 101
    );

    const RolePrivileges = array(
        self::Role["kRoleAttendee"] => self::AttendeePrivileges,
        self::Role["kRolePublisher"] => self::PublisherPrivileges,
        self::Role["kRoleSubscriber"] => self::SubscriberPrivileges,
        self::Role["kRoleAdmin"] => self::AdminPrivileges
    );

    public $token;

    /**
     * AgoraTokenGenerator constructor.
     * @param $appID
     * @param $appCertificate
     * @param $channelName
     * @param $uid
     * @throws \Exception
     */
    public function __construct($appID, $appCertificate, $channelName, $uid){
        $this->token = new AccessToken();
        $this->token->appID = $appID;
        $this->token->appCertificate = $appCertificate;
        $this->token->channelName = $channelName;
        $this->token->setUid($uid);
    }

    /**
     * @param $token
     * @param $appCertificate
     * @param $channel
     * @param $uid
     * @throws \Exception
     */
    public function initWithToken($token, $appCertificate, $channel, $uid){
        $this->token = AccessToken::initWithToken($token, $appCertificate, $channel, $uid);
    }

    /**
     * @param $role
     */
    public function initPrivilege($role){
        $p = self::RolePrivileges[$role];
        foreach($p as $key => $value){
            $this->setPrivilege($key, $value);
        }
    }

    /**
     * @param $privilege
     * @param $expireTimestamp
     */
    public function setPrivilege($privilege, $expireTimestamp){
        $this->token->addPrivilege($privilege, $expireTimestamp);
    }

    /**
     * @param $privilege
     */
    public function removePrivilege($privilege){
        unset($this->token->message->privileges[$privilege]);
    }

    /**
     * @return string
     */
    public function buildToken(){
        return $this->token->build();
    }
}
