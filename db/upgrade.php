<?php  
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_block_sharedropbox_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }
    if ($result && $oldversion < 2007071302) {

    /// Define table search_documents to be created
        $table = new XMLDBTable('sharedropbox_comments');

    /// Drop it if it existed before
    /// drop_table($table, true, false);

    /// Adding fields to table search_documents
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('datetimeno', XMLDB_TYPE_INTEGER, '14', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('submission', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('sid', XMLDB_TYPE_INTEGER, '6', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('comment', XMLDB_TYPE_TEXT, '', null, XMLDB_NOTNULL, null, null, null, null);
        
    /// Adding keys to table search_documents
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table search_documents
        $table->addIndexInfo('submission', XMLDB_INDEX_UNIQUE, array('submission','sid','datetimeno'));

    /// Launch create table for search_documents
        $result = $result && create_table($table);
        
        
        $table = new XMLDBTable('sharedropbox_likes');

    /// Drop it if it existed before
    /// drop_table($table, true, false);

    /// Adding fields to table search_documents
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('submission', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('sid', XMLDB_TYPE_INTEGER, '6', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        
    /// Adding keys to table search_documents
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table search_documents
        $table->addIndexInfo('submission', XMLDB_INDEX_UNIQUE, array('submission','sid'));

    /// Launch create table for search_documents
        $result = $result && create_table($table);
    }
                                                       
    return $result;
}

?>