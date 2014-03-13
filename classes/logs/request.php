<?php

/**
 * @package ContentSync
 * @class   ContentSyncLogRequest
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    09 Jan 2014
 * */
class ContentSyncLogRequest extends ContentSyncLog {

    public static function definition() {
        return array(
            'fields'              => array(
                'id'                      => array(
                    'name'     => 'ID',
                    'datatype' => 'integer',
                    'default'  => 0,
                    'required' => true
                ),
                'object_id'               => array(
                    'name'     => 'ObjectID',
                    'datatype' => 'integer',
                    'default'  => 0,
                    'required' => true
                ),
                'object_version'          => array(
                    'name'     => 'ObjectVersion',
                    'datatype' => 'integer',
                    'default'  => 0,
                    'required' => true
                ),
                'object_version_language' => array(
                    'name'     => 'ObjectVersionLanguage',
                    'datatype' => 'string',
                    'default'  => '',
                    'required' => false
                ),
                'object_data'             => array(
                    'name'     => 'ObjectData',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'url'                     => array(
                    'name'     => 'URL',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'response_status'         => array(
                    'name'     => 'ResponseStatus',
                    'datatype' => 'integer',
                    'default'  => 0,
                    'required' => false
                ),
                'response_headers'        => array(
                    'name'     => 'ResponseHeaders',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'response_error'          => array(
                    'name'     => 'ResponseError',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'response'                => array(
                    'name'     => 'Response',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'response_time'           => array(
                    'name'     => 'ResponseTime',
                    'datatype' => 'float',
                    'default'  => 0,
                    'required' => false
                ),
                'date'                    => array(
                    'name'     => 'Date',
                    'datatype' => 'integer',
                    'default'  => time(),
                    'required' => true
                )
            ),
            'function_attributes' => array(
                'object'  => 'getObject',
                'version' => 'getVersion'
            ),
            'keys'                => array( 'id' ),
            'sort'                => array( 'id' => 'desc' ),
            'increment_key'       => 'id',
            'class_name'          => __CLASS__,
            'name'                => 'content_sync_log_request'
        );
    }

}
