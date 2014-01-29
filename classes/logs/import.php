<?php
/**
 * @package ContentSync
 * @class   ContentSyncLogImport
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    16 Jan 2014
 **/

class ContentSyncLogImport extends ContentSyncLog
{
	const STATUS_CREATED = 1;
	const STATUS_UPDATED = 2;
	const STATUS_SKIPPED = 3;
	const STATUS_REMOVED = 4;

	public static function definition() {
		return array(
			'fields'              => array(
				'id' => array(
					'name'     => 'ID',
					'datatype' => 'integer',
					'default'  => 0,
					'required' => true
				),
				'user_id' => array(
					'name'     => 'UserID',
					'datatype' => 'integer',
					'default'  => eZUser::currentUserID(),
					'required' => true
				),
				'object_id' => array(
					'name'     => 'ObjectID',
					'datatype' => 'integer',
					'default'  => 0,
					'required' => true
				),
				'object_version' => array(
					'name'     => 'ObjectVersion',
					'datatype' => 'integer',
					'default'  => 0,
					'required' => true
				),
				'object_data' => array(
					'name'     => 'ObjectData',
					'datatype' => 'string',
					'default'  => null,
					'required' => false
				),
				'status' => array(
					'name'     => 'Status',
					'datatype' => 'integer',
					'default'  => 0,
					'required' => false
				),
				'import_time' => array(
					'name'     => 'ImportTime',
					'datatype' => 'float',
					'default'  => 0,
					'required' => false
				),
				'error' => array(
					'name'     => 'Error',
					'datatype' => 'string',
					'default'  => null,
					'required' => false
				),
				'result' => array(
					'name'     => 'Result',
					'datatype' => 'string',
					'default'  => null,
					'required' => false
				),
				'date' => array(
					'name'     => 'Date',
					'datatype' => 'integer',
					'default'  => time(),
					'required' => true
				)
			),
			'function_attributes' => array(
				'object'             => 'getObject',
				'version'            => 'getVersion',
				'user'               => 'getUser',
				'status_description' => 'getStatusDescription'
			),
			'keys'                => array( 'id' ),
			'sort'                => array( 'id' => 'desc' ),
			'increment_key'       => 'id',
			'class_name'          => __CLASS__,
			'name'                => 'content_sync_log_import'
		);
	}

	public function getUser() {
		if( isset( $this->cache['user'] ) === false ) {
			$this->cache['user'] = eZContentObject::fetch( $this->attribute( 'user_id' ) );
		}

		return $this->cache['user'];
	}

	public function getStatusDescription() {
		switch( $this->attribute( 'status' ) ) {
			case self::STATUS_CREATED:
				return ezpI18n::tr( 'extension/content_sync', 'Object is created' );
			case self::STATUS_UPDATED:
				return ezpI18n::tr( 'extension/content_sync', 'Object is updated' );
			case self::STATUS_SKIPPED:
				return ezpI18n::tr( 'extension/content_sync', 'Skipped' );
			case self::STATUS_REMOVED:
				return ezpI18n::tr( 'extension/content_sync', 'Removed' );
			default:
				return ezpI18n::tr( 'extension/content_sync', 'Unknown' );
		}
	}
}
