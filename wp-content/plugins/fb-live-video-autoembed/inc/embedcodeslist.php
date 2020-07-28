<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class fbjkembedcodes_List_Table extends WP_List_Table {
  
  
  function get_columns()
  {
    $columns = array(
      'id'             => 'ID',
      'vidsource'      => 'Source',
      'videotype'      => 'Video Type',
      'embedcode'      => 'Embed Code',
      'width'          => 'Player Width',
    );
    return $columns;
  }

  function prepare_items() 
  {
    $columns = $this->get_columns();
    $hidden = array();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, $hidden, $sortable);
    
    $per_page     = $this->get_items_per_page( 'rows_per_page', 10 );
    $current_page = $this->get_pagenum();
    $total_items  = self::record_count();

    $this->set_pagination_args( [
      'total_items' => $total_items, //WE have to calculate the total number of items
      'per_page'    => $per_page //WE have to determine how many items to show on a page
    ] );


    $this->items = self::getrows( $per_page, $current_page );
    
    
    /*$tabledata = $this->getrows();
    usort( $tabledata, array( &$this, 'usort_reorder' ) );
    $this->items = $tabledata;*/
  }
  
  public static function getrows( $per_page = 5, $page_number = 1 ) {
    global $wpdb;
    $sql = "SELECT * FROM {$wpdb->prefix}fbjkembedcodes";
    $sql .= ' ORDER BY ';
    $sql .= ! empty( $_REQUEST['orderby'] ) ? ' ' . esc_sql( $_REQUEST['orderby'] ) : 'id';
    $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' desc';
    
    $sql .= " LIMIT $per_page";
    $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
    $result = $wpdb->get_results( $sql, 'ARRAY_A' );
    return $result;
  }
  
  public static function record_count() {
    global $wpdb;

    $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}fbjkembedcodes";

    return $wpdb->get_var( $sql );
  }

  function column_default( $item, $column_name ) 
  {
    switch( $column_name ) { 
      case 'vidsource':
        $actions = [
          'edit'  => '<a class="edit-embedcode" href="'.admin_url('admin.php') . '?page=fbjk-livevideo-autoembed-embedcode-form&embedcodeid='.$item['id']
          .'">Edit</a>',
          'delete' => '<a class="delete-reminder" href="'.admin_url('admin.php') . '?page=fbjk-livevideo-autoembed-embedcodes&delete=1&embedcodeid='.$item['id']
          .'" onclick="return confirm(\'Are you sure you want to delete this embed code?\')">Delete</a>'
        ];
        if($item['vidsource'] == 'me')
        {
          $source = 'My Facebook User Account';
        }
        elseif($item['vidsource'] == 'myfbpage')
        {
          $source = 'Page: '.$item['pageusername'];
        }
        elseif($item['vidsource'] == 'myfbgroup')
        {
          $source = 'Group: '.$item['groupid'];
        }
        return $source . $this->row_actions( $actions );
      case 'videotype':
        if($item[ $column_name ] == "live")
          $videotype = "Currently Live Video";
        elseif($item[ $column_name ] == "recorded")
          $videotype = "Recently Recorded Live Videos (Maximum: ".$item['maxrecorded'].")";
        else
          $videotype = "Recently Uploaded Videos (Maximum: ".$item['maxrecorded'].")";
        return $videotype;
      case 'embedcode':
        return '[fblivevideoembed id="'.$item['id'].'"]';
      case 'actions':
        return '<a class="edit-embedcode" href="'.admin_url('admin.php') . '?page=fbjk-livevideo-autoembed-embedcode-form&embedcodeid='.$item['id']
          .'">Edit</a>&nbsp;&nbsp;<a class="delete-reminder" href="'.admin_url('admin.php') . '?page=fbjk-livevideo-autoembed-embedcodes&delete=1&embedcodeid='.$item['id']
          .'" onclick="return confirm(\'Are you sure you want to delete this embed code?\')">Delete</a>';
        
      default:
        return $item[ $column_name ];
    }
  }
  
  function get_sortable_columns() 
  {
    $sortable_columns = array(
      'id'  => array('id',false),
      'pageusername' => array('pageusername',false)
    );
    return $sortable_columns;
  }
  
}


