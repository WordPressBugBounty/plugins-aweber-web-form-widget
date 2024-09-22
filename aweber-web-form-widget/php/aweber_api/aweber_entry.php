<?php
namespace AWeberWebFormPluginNamespace;

class AWeberEntry extends AWeberResponse {

    /**
     * @var array Holds list of data keys that are not publicly accessible
     */
    protected $_privateData = array(
        'resource_type_link',
        'http_etag',
    );

    /**
     * @var array   Stores local modifications that have not been saved
     */
    protected $_localDiff = array();

    /**
     * @var array Holds AWeberCollection objects already instantiated, keyed by
     *      their resource name (plural)
     */
    protected $_collections = array();

    /**
     * attrs
     *
     * Provides a simple array of all the available data (and collections) available
     * in this entry.
     *
     * @access public
     * @return array
     */
    public function attrs() {
        $attrs = array();
        foreach ($this->data as $key => $value) {
            if (!in_array($key, $this->_privateData) && !strpos($key, 'collection_link')) {
                $attrs[$key] = $value;
            }
        }
        if (!empty(AWeberAPI::$_collectionMap[$this->type])) {
            foreach (AWeberAPI::$_collectionMap[$this->type] as $child) {
                $attrs[$child] = 'collection';
            }
        }
        return $attrs;
    }

    /**
     * _type 
     *
     * Used to pull the name of this resource from its resource_type_link 
     * @access protected
     * @return String
     */
    protected function _type() {
        if (empty($this->type)) {
            $typeLink = $this->data['resource_type_link'];
            if (empty($typeLink)) return null;
            list($url, $type) = explode('#', $typeLink);
            $this->type = $type;
        }
        return $this->type;
    }

    /**
     * move
     *
     * Invoke the API method to MOVE an entry resource to a different List.
     *
     * Note: Not all entry resources are eligible to be moved, please
     *       refer to the AWeber API Reference Documentation at
     *       https://labs.aweber.com/docs/reference/1.0 for more
     *       details on which entry resources may be moved and if there
     *       are any requirements for moving that resource.
     *
     * @access public
     * @param AWeberEntry(List)   List to move Resource (this) too.
     * @return mixed AWeberEntry(Resource) Resource created on List ($list)
     *                                     or False if resource was not created.
     */
    public function move($list, $last_followup_message_number_sent=NULL) {
        # Move Resource
        $params = array(
                        'ws.op' => 'move',
                        'list_link' => $list->self_link
                    );
        if (isset($last_followup_message_number_sent)) {
            $params['last_followup_message_number_sent'] = $last_followup_message_number_sent;
        }

        $data = $this->adapter->request('POST', $this->url, $params, array('return' => 'headers'));

        # Return new Resource
        $url = $data['Location'];
        $resource_data = $this->adapter->request('GET', $url);
        return new AWeberEntry($resource_data, $url, $this->adapter);
    }

    /**
     * __get
     *
     * Used to look up items in data, and special properties like type and 
     * child collections dynamically.
     *
     * @param String $value     Attribute being accessed  
     * @access public
     * @throws AWeberResourceNotImplemented
     * @return mixed
     */
    public function __get($value) {
        if (in_array($value, $this->_privateData)) {
            return null;
        }
        if (!empty($this->data) && array_key_exists($value, $this->data)) {
            if (is_array($this->data[$value])) {
                $array = new AWeberEntryDataArray($this->data[$value], $value, $this);
                $this->data[$value] = $array;
            }
            return $this->data[$value];
        }
        if ($value == 'type') return $this->_type();
        if ($this->_isChildCollection($value)) {
            return $this->_getCollection($value);
        }
        throw new AWeberResourceNotImplemented($this, $value);
    }

    /**
     * __set
     *
     * If the key provided is part of the data array, then update it in the
     * data array.  Otherwise, use the default __set() behavior.
     *
     * @param mixed $key        Key of the attr being set
     * @param mixed $value      Value being set to the $key attr
     * @access public
     */
    public function __set($key, $value) {
        if (array_key_exists($key, $this->data)) {
            $this->_localDiff[$key] = $value;
            return $this->data[$key] = $value;
        } else {
            return parent::__set($key, $value);
        }
    }

    /**
     * findSubscribers
     *
     * Looks through all lists for subscribers
     * that match the given filter
     * @access public
     * @return AWeberCollection
     */
    public function findSubscribers($search_data) {
        $this->_methodFor(array('account'));
        $params = array_merge($search_data, array('ws.op' => 'findSubscribers'));
        $data = $this->adapter->request('GET', $this->url, $params);

        $ts_params = array_merge($params, array('ws.show' => 'total_size'));
        $total_size = $this->adapter->request('GET', $this->url, $ts_params, array('return' => 'integer'));

        # return collection
        $data['total_size'] = $total_size;
        $url = $this->url . '?'. http_build_query($params);
        return new AWeberCollection($data, $url, $this->adapter);
    }

    /**
     * getActivity
     *
     * Returns analytics activity for a given subscriber
     * @access public
     * @return AWeberCollection
     */
    public function getActivity() {
        $this->_methodFor(array('subscriber'));
        $params = array('ws.op' => 'getActivity');
        $data = $this->adapter->request('GET', $this->url, $params);

        $ts_params = array_merge($params, array('ws.show' => 'total_size'));
        $total_size = $this->adapter->request('GET', $this->url, $ts_params, array('return' => 'integer'));

        # return collection
        $data['total_size'] = $total_size;
        $url = $this->url . '?'. http_build_query($params);
        return new AWeberCollection($data, $url, $this->adapter);
    }

    /** getParentEntry
     *
     * Gets an entry's parent entry
     * Returns NULL if no parent entry
     */
    public function getParentEntry(){
        $url_parts = explode('/', $this->url);
        $size = count($url_parts);

        #Remove entry id and slash from end of url
        $url = substr($this->url, 0, -strlen($url_parts[$size-1])-1);

        #Remove collection name and slash from end of url
        $url = substr($url, 0, -strlen($url_parts[$size-2])-1);

        try {
            $data = $this->adapter->request('GET', $url);
            return new AWeberEntry($data, $url, $this->adapter);
        } catch (Exception $e) {
            return NULL;
        }
    }

    private function _fetchRecords($url) {
        $collection = array();
        while (isset($url)) {
            $data = $this->adapter->request('GET', $url, array(), array('allow_empty' => true));
            $collection = array_merge($data['entries'], $collection);
            $url = isset($data['next_collection_link']) ? $data['next_collection_link'] : null;
        }
        return $collection;
    } 

    /**
     * getWebForms
     *
     * Gets all web_forms for this account
     * @access public
     * @return array
     */
    public function getWebForms() {
        $this->_methodFor(array('account'));
        $collection = $this->_fetchRecords($this->url.'?ws.op=getWebForms');
        return $this->_parseNamedOperation($collection);
    }


    /**
     * getWebFormSplitTests
     *
     * Gets all web_form split tests for this account
     * @access public
     * @return array
     */
    public function getWebFormSplitTests() {
        $this->_methodFor(array('account'));
        $collection = $this->_fetchRecords($this->url.'?ws.op=getWebFormSplitTests');
        return $this->_parseNamedOperation($collection);
    }

    /**
     * getWebFormsForList
     *
     * Gets all web_form for this account and list
     * @access public
     * @return array
     */
    public function getWebFormsForList($list_id) {
        $url = $this->url . '/lists/'. $list_id . '/web_forms';
        $collection = $this->_fetchRecords($url);
        return $this->_parseNamedOperation($collection);
    }

    /**
     * getWebFormSplitTestsForList
     *
     * Gets all web_form split tests for this account and list
     * @access public
     * @return array
     */
    public function getWebFormSplitTestsForList($list_id) {
        $url = $this->url . '/lists/'. $list_id . '/web_form_split_tests';
        $collection = $this->_fetchRecords($url);
        return $this->_parseNamedOperation($collection);
    }

    /**
     * _parseNamedOperation
     *
     * Turns a dumb array of json into an array of Entries.  This is NOT 
     * a collection, but simply an array of entries, as returned from a
     * named operation.
     *
     * @param array $data 
     * @access protected
     * @return array
     */
    protected function _parseNamedOperation($data) {
        $results = array();
        foreach($data as $entryData) {
            $results[] = new AWeberEntry($entryData, str_replace($this->adapter->app->getBaseUri(), '',
             $entryData['self_link']), $this->adapter);
        }
        return $results;
    }

    /**
     * _methodFor
     *
     * Raises exception if $this->type is not in array entryTypes.
     * Used to restrict methods to specific entry type(s).
     * @param mixed $entryTypes Array of entry types as strings, ie array('account')
     * @access protected
     * @return void
     */
    protected function _methodFor($entryTypes) {
        if (in_array($this->type, $entryTypes)) return true;
        throw new AWeberMethodNotImplemented($this);
    }

    /**
     * _getCollection 
     *
     * Returns the AWeberCollection object representing the given
     * collection name, relative to this entry.
     *
     * @param String $value The name of the sub-collection
     * @access protected
     * @return AWeberCollection
     */
    protected function _getCollection($value) {
        if (empty($this->_collections[$value])) {
            $url = "{$this->url}/{$value}";
            $data = $this->adapter->request('GET', $url);
            $this->_collections[$value] = new AWeberCollection($data, $url, $this->adapter);
        }
        return $this->_collections[$value];
    }


    /**
     * _isChildCollection
     *
     * Is the given name of a collection a child collection of this entry?
     *
     * @param String $value The name of the collection we are looking for
     * @access protected
     * @return boolean
     * @throws AWeberResourceNotImplemented
     */
    protected function _isChildCollection($value) {
        $this->_type();
        if (!empty(AWeberAPI::$_collectionMap[$this->type]) &&
            in_array($value, AWeberAPI::$_collectionMap[$this->type])) return true;
        return false;
    }

    /**
     * create subscriber
     *
     * Invoke the API method to CREATE a new Subscriber resource.
     *
     * @access public
     * @param params mixed  associtative array of key/value pairs.
     * @param list_id integer listId to add subscriber
     * @return boolean
     */
    public function create_subscriber($list_id, $kv_pairs) {
        $url = $this->url . '/lists/' . $list_id . '/subscribers';
        $this->adapter->request('POST', $url, $kv_pairs, array('return' => 'headers'));

        return true;
    }
}
