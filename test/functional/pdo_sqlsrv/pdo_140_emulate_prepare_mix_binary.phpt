--TEST--
Test emulate prepare with mix bound param encodings including binary data
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
class MyStatement extends PDOStatement
{
    public function bindValues(array &$values, $placeholder_prefix, $columnInformation)
    {
        $max_placeholder = 0;
        foreach ($values as $field_name => &$field_value) {
            $placeholder = $placeholder_prefix . $max_placeholder++;
            if (isset($columnInformation['blobs'][$field_name])) {
                $this->bindParam($placeholder, $field_value, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
            } else {
                // Even though not a blob, make sure we retain a copy of these values.
                $this->bindParam($placeholder, $field_value, PDO::PARAM_STR);
            }
        }
    }
}

//*******************************************************
// TEST BEGIN
//*******************************************************
require_once("MsCommon_mid-refactor.inc");

try {
    $cnn = connect();
    $cnn->setAttribute(PDO::ATTR_STATEMENT_CLASS, [MyStatement::class]);

    // Drop
    $tbname = "watchdog";
    dropTable($cnn, $tbname);

    $pdo_options = array();
    if (!isAEConnected()) {
        $pdo_options[PDO::ATTR_EMULATE_PREPARES] = true;
        $pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = true;

        $cm_arr = array(new ColumnMeta("int", "wid", "IDENTITY(1,1) NOT NULL"),
                        new ColumnMeta("int", "uid", "NOT NULL CONSTRAINT [watchdog_uid_df]  DEFAULT ((0))"),
                        new ColumnMeta("nvarchar(64)", "type", "NOT NULL CONSTRAINT [watchdog_type_df]  DEFAULT ('')"),
                        new ColumnMeta("nvarchar(max)", "message", "NOT NULL"),
                        new ColumnMeta("varbinary(max)", "variables", "NOT NULL"),
                        new ColumnMeta("smallint", "severity", "NOT NULL CONSTRAINT [watchdog_severity_df]  DEFAULT ((0))"),
                        new ColumnMeta("nvarchar(255)", "link", "NULL CONSTRAINT [watchdog_link_df]  DEFAULT ('')"),
                        new ColumnMeta("nvarchar(max)", "location", "NOT NULL"),
                        new ColumnMeta("nvarchar(max)", "referer", "NULL"),
                        new ColumnMeta("nvarchar(128)", "hostname", "NOT NULL CONSTRAINT [watchdog_hostname_df]  DEFAULT ('')"),
                        new ColumnMeta("int", "timestamp", "NOT NULL CONSTRAINT [watchdog_timestamp_df]  DEFAULT ((0))"));
    } else {
        // Emulate prepare and using direct query for binding parameters are not supported in Always encrypted
        $pdo_options[PDO::ATTR_EMULATE_PREPARES] = false;
        $pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = false;

        // Default constraints are unsupported on encrypted columns
        $cm_arr = array(new ColumnMeta("int", "wid", "IDENTITY(1,1) NOT NULL"),
                        new ColumnMeta("int", "uid", "NOT NULL"),
                        new ColumnMeta("nvarchar(64)", "type", "NOT NULL"),
                        new ColumnMeta("nvarchar(max)", "message", "NOT NULL"),
                        new ColumnMeta("varbinary(max)", "variables", "NOT NULL"),
                        new ColumnMeta("smallint", "severity", "NOT NULL"),
                        new ColumnMeta("nvarchar(255)", "link"),
                        new ColumnMeta("nvarchar(max)", "location", "NOT NULL"),
                        new ColumnMeta("nvarchar(max)", "referer"),
                        new ColumnMeta("nvarchar(128)", "hostname", "NOT NULL"),
                        new ColumnMeta("int", "timestamp", "NOT NULL"));
    }

    $pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
    $pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;

    $cd_arr = array();
    foreach ($cm_arr as $cm) {
        array_push($cd_arr, $cm->getColDef());
    }

    $tablescript = "CREATE TABLE [dbo].[$tbname](
                    $cd_arr[0], $cd_arr[1], $cd_arr[2], $cd_arr[3], $cd_arr[4], $cd_arr[5], $cd_arr[6], $cd_arr[7], $cd_arr[8], $cd_arr[9], $cd_arr[10],
                    CONSTRAINT [watchdog_pkey] PRIMARY KEY CLUSTERED
                    (
                    [wid] ASC
                    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
                    ) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]";

    // Recreate
    $st = $cnn->prepare($tablescript);
    $st->execute();

    $query = "INSERT INTO [$tbname] ([uid], [type], [message], [variables], [severity], [link], [location], [referer], [hostname], [timestamp]) OUTPUT (Inserted.wid) VALUES
             (:db_insert0, :db_insert1, :db_insert2, :db_insert3, :db_insert4, :db_insert5, :db_insert6, :db_insert7, :db_insert8, :db_insert9)";

    $values_encoded = 'YToxMDp7czozOiJ1aWQiO2k6MDtzOjQ6InR5cGUiO3M6MzoicGhwIjtzOjc6Im1lc3NhZ2UiO3M6NTE6IiV0eXBlOiBAbWVzc2FnZSBpbiAlZnVuY3Rpb24gKGxpbmUgJWxpbmUgb2YgJWZpbGUpLiI7czo5OiJ2YXJpYWJsZXMiO3M6MjE4ODoiYTo1OntzOjU6IiV0eXBlIjtzOjQ1OiJEcnVwYWxcQ29yZVxEYXRhYmFzZVxEYXRhYmFzZUV4Y2VwdGlvbldyYXBwZXIiO3M6ODoiQG1lc3NhZ2UiO3M6MTkxMzoiU1FMU1RBVEVbSU1TU1BdOiBBbiBlcnJvciBvY2N1cnJlZCB0cmFuc2xhdGluZyB0aGUgcXVlcnkgc3RyaW5nIHRvIFVURi0xNjogTm8gbWFwcGluZyBmb3IgdGhlIFVuaWNvZGUgY2hhcmFjdGVyIGV4aXN0cyBpbiB0aGUgdGFyZ2V0IG11bHRpLWJ5dGUgY29kZSBwYWdlLg0KLjogTUVSR0UgSU5UTyBbY2FjaGVfZGF0YV0gX3RhcmdldA0KVVNJTkcgKFNFTEVDVCBULiogRlJPTSAodmFsdWVzKDpkYl9pbnNlcnRfcGxhY2Vob2xkZXJfMCwgOmRiX2luc2VydF9wbGFjZWhvbGRlcl8xLCA6ZGJfaW5zZXJ0X3BsYWNlaG9sZGVyXzIsIDpkYl9pbnNlcnRfcGxhY2Vob2xkZXJfMywgOmRiX2luc2VydF9wbGFjZWhvbGRlcl80LCA6ZGJfaW5zZXJ0X3BsYWNlaG9sZGVyXzUsIDpkYl9pbnNlcnRfcGxhY2Vob2xkZXJfNikpIGFzIFQoW2NpZF0sIFtleHBpcmVdLCBbY3JlYXRlZF0sIFt0YWdzXSwgW2NoZWNrc3VtXSwgW2RhdGFdLCBbc2VyaWFsaXplZF0pKSBfc291cmNlDQpPTiBfdGFyZ2V0LltjaWRdID0gX3NvdXJjZS5bY2lkXQ0KV0hFTiBNQVRDSEVEIFRIRU4gVVBEQVRFIFNFVCBfdGFyZ2V0LltleHBpcmVdID0gX3NvdXJjZS5bZXhwaXJlXSwgX3RhcmdldC5bY3JlYXRlZF0gPSBfc291cmNlLltjcmVhdGVkXSwgX3RhcmdldC5bdGFnc10gPSBfc291cmNlLlt0YWdzXSwgX3RhcmdldC5bY2hlY2tzdW1dID0gX3NvdXJjZS5bY2hlY2tzdW1dLCBfdGFyZ2V0LltkYXRhXSA9IF9zb3VyY2UuW2RhdGFdLCBfdGFyZ2V0LltzZXJpYWxpemVkXSA9IF9zb3VyY2UuW3NlcmlhbGl6ZWRdDQpXSEVOIE5PVCBNQVRDSEVEIFRIRU4gSU5TRVJUIChbY2lkXSwgW2V4cGlyZV0sIFtjcmVhdGVkXSwgW3RhZ3NdLCBbY2hlY2tzdW1dLCBbZGF0YV0sIFtzZXJpYWxpemVkXSkgVkFMVUVTIChfc291cmNlLltjaWRdLCBfc291cmNlLltleHBpcmVdLCBfc291cmNlLltjcmVhdGVkXSwgX3NvdXJjZS5bdGFnc10sIF9zb3VyY2UuW2NoZWNrc3VtXSwgX3NvdXJjZS5bZGF0YV0sIF9zb3VyY2UuW3NlcmlhbGl6ZWRdKQ0KT1VUUFVUICRhY3Rpb247OyBBcnJheQooCiAgICBbOmRiX2luc2VydF9wbGFjZWhvbGRlcl8wXSA9PiBBcnJheQogICAgICAgICgKICAgICAgICAgICAgW3ZhbHVlXSA9PiByb3V0ZTovOlhERUJVR19TRVNTSU9OX1NUQVJUPTU4RTFDMUM0CiAgICAgICAgICAgIFtkYXRhdHlwZV0gPT4gMgogICAgICAgICkKCiAgICBbOmRiX2luc2VydF9wbGFjZWhvbGRlcl8xXSA9PiBBcnJheQogICAgICAgICgKICAgICAgICAgICAgW3ZhbHVlXSA9PiAtMQogICAgICAgICAgICBbZGF0YXR5cGVdID0+IDIKICAgICAgICApCgogICAgWzpkYl9pbnNlcnRfcGxhY2Vob2xkZXJfMl0gPT4gQXJyYXkKICAgICAgICAoCiAgICAgICAgICAgIFt2YWx1ZV0gPT4gMTQ3MDIwNTc3My43CiAgICAgICAgICAgIFtkYXRhdHlwZV0gPT4gMgogICAgICAgICkKCiAgICBbOmRiX2luc2VydF9wbGFjZWhvbGRlcl8zXSA9PiBBcnJheQogICAgICAgICgKICAgICAgICAgICAgW3ZhbHVlXSA9PiByb3V0ZV9tYXRjaAogICAgICAgICAgICBbZGF0YXR5cGVdID0+IDIKICAgICAgICApCgogICAgWzpkYl9pbnNlcnRfcGxhY2Vob2xkZXJfNF0gPT4gQXJyYXkKICAgICAgICAoCiAgICAgICAgICAgIFt2YWx1ZV0gPT4gNAogICAgICAgICAgICBbZGF0YXR5cGVdID0+IDIKICAgICAgICApCgogICAgWzpkYl9pbnNlcnRfcGxhY2Vob2xkZXJfNV0gPT4gQXJyYXkKICAgICAgICAoCiAgICAgICAgICAgIFt2YWx1ZV0gPT4gUmVzb3VyY2UgaWQgIzQKICAgICAgICAgICAgW2RhdGF0eXBlXSA9PiAzCiAgICAgICAgKQoKICAgIFs6ZGJfaW5zZXJ0X3BsYWNlaG9sZGVyXzZdID0+IEFycmF5CiAgICAgICAgKAogICAgICAgICAgICBbdmFsdWVdID0+IDEKICAgICAgICAgICAgW2RhdGF0eXBlXSA9PiAyCiAgICAgICAgKQoKKQoiO3M6OToiJWZ1bmN0aW9uIjtzOjY1OiJEcnVwYWxcQ29yZVxSb3V0aW5nXFJvdXRlUHJvdmlkZXItPmdldFJvdXRlQ29sbGVjdGlvbkZvclJlcXVlc3QoKSI7czo1OiIlZmlsZSI7czo1MjoiRDpcZDhcY29yZVxsaWJcRHJ1cGFsXENvcmVcUm91dGluZ1xSb3V0ZVByb3ZpZGVyLnBocCI7czo1OiIlbGluZSI7aToxNjc7fSI7czo4OiJzZXZlcml0eSI7aTozO3M6NDoibGluayI7czowOiIiO3M6ODoibG9jYXRpb24iO3M6NjQ6Imh0dHA6Ly9sb2NhbC5kN3Rlc3QuY29tL2luZGV4LnBocC8/WERFQlVHX1NFU1NJT05fU1RBUlQ9NThFMUMxQzQiO3M6NzoicmVmZXJlciI7czowOiIiO3M6ODoiaG9zdG5hbWUiO3M6OToiMTI3LjAuMC4xIjtzOjk6InRpbWVzdGFtcCI7aToxNDcwMjA1Nzc0O30=';
    $columninformation_encoded = 'YTo3OntzOjg6ImlkZW50aXR5IjtzOjM6IndpZCI7czoxMDoiaWRlbnRpdGllcyI7YToxOntzOjM6IndpZCI7czozOiJ3aWQiO31zOjc6ImNvbHVtbnMiO2E6MTE6e3M6Mzoid2lkIjthOjE0OntzOjQ6Im5hbWUiO3M6Mzoid2lkIjtzOjEwOiJtYXhfbGVuZ3RoIjtpOjQ7czo5OiJwcmVjaXNpb24iO2k6MTA7czoxNDoiY29sbGF0aW9uX25hbWUiO047czoxMToiaXNfbnVsbGFibGUiO2k6MDtzOjE0OiJpc19hbnNpX3BhZGRlZCI7aTowO3M6MTE6ImlzX2lkZW50aXR5IjtpOjE7czoxMToiaXNfY29tcHV0ZWQiO2k6MDtzOjQ6InR5cGUiO3M6MzoiaW50IjtzOjEwOiJkZWZpbml0aW9uIjtOO3M6MTM6ImRlZmF1bHRfdmFsdWUiO047czoxMToic3Fsc3J2X3R5cGUiO3M6MzoiaW50IjtzOjEyOiJkZXBlbmRlbmNpZXMiO2E6MDp7fXM6NzoiaW5kZXhlcyI7YToxOntpOjA7czoxMzoid2F0Y2hkb2dfcGtleSI7fX1zOjM6InVpZCI7YToxNDp7czo0OiJuYW1lIjtzOjM6InVpZCI7czoxMDoibWF4X2xlbmd0aCI7aTo0O3M6OToicHJlY2lzaW9uIjtpOjEwO3M6MTQ6ImNvbGxhdGlvbl9uYW1lIjtOO3M6MTE6ImlzX251bGxhYmxlIjtpOjA7czoxNDoiaXNfYW5zaV9wYWRkZWQiO2k6MDtzOjExOiJpc19pZGVudGl0eSI7aTowO3M6MTE6ImlzX2NvbXB1dGVkIjtpOjA7czo0OiJ0eXBlIjtzOjM6ImludCI7czoxMDoiZGVmaW5pdGlvbiI7TjtzOjEzOiJkZWZhdWx0X3ZhbHVlIjtzOjU6IigoMCkpIjtzOjExOiJzcWxzcnZfdHlwZSI7czozOiJpbnQiO3M6MTI6ImRlcGVuZGVuY2llcyI7YTowOnt9czo3OiJpbmRleGVzIjthOjE6e2k6MDtzOjc6InVpZF9pZHgiO319czo0OiJ0eXBlIjthOjE0OntzOjQ6Im5hbWUiO3M6NDoidHlwZSI7czoxMDoibWF4X2xlbmd0aCI7aTo2NDtzOjk6InByZWNpc2lvbiI7aTowO3M6MTQ6ImNvbGxhdGlvbl9uYW1lIjtzOjIwOiJMYXRpbjFfR2VuZXJhbF9DSV9BSSI7czoxMToiaXNfbnVsbGFibGUiO2k6MDtzOjE0OiJpc19hbnNpX3BhZGRlZCI7aToxO3M6MTE6ImlzX2lkZW50aXR5IjtpOjA7czoxMToiaXNfY29tcHV0ZWQiO2k6MDtzOjQ6InR5cGUiO3M6NzoidmFyY2hhciI7czoxMDoiZGVmaW5pdGlvbiI7TjtzOjEzOiJkZWZhdWx0X3ZhbHVlIjtzOjQ6IignJykiO3M6MTE6InNxbHNydl90eXBlIjtzOjExOiJ2YXJjaGFyKDY0KSI7czoxMjoiZGVwZW5kZW5jaWVzIjthOjA6e31zOjc6ImluZGV4ZXMiO2E6MTp7aTowO3M6ODoidHlwZV9pZHgiO319czo3OiJtZXNzYWdlIjthOjEzOntzOjQ6Im5hbWUiO3M6NzoibWVzc2FnZSI7czoxMDoibWF4X2xlbmd0aCI7aTotMTtzOjk6InByZWNpc2lvbiI7aTowO3M6MTQ6ImNvbGxhdGlvbl9uYW1lIjtzOjIwOiJMYXRpbjFfR2VuZXJhbF9DSV9BSSI7czoxMToiaXNfbnVsbGFibGUiO2k6MDtzOjE0OiJpc19hbnNpX3BhZGRlZCI7aToxO3M6MTE6ImlzX2lkZW50aXR5IjtpOjA7czoxMToiaXNfY29tcHV0ZWQiO2k6MDtzOjQ6InR5cGUiO3M6ODoibnZhcmNoYXIiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7TjtzOjExOiJzcWxzcnZfdHlwZSI7czoxMzoibnZhcmNoYXIobWF4KSI7czoxMjoiZGVwZW5kZW5jaWVzIjthOjA6e319czo5OiJ2YXJpYWJsZXMiO2E6MTM6e3M6NDoibmFtZSI7czo5OiJ2YXJpYWJsZXMiO3M6MTA6Im1heF9sZW5ndGgiO2k6LTE7czo5OiJwcmVjaXNpb24iO2k6MDtzOjE0OiJjb2xsYXRpb25fbmFtZSI7TjtzOjExOiJpc19udWxsYWJsZSI7aTowO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjE7czoxMToiaXNfaWRlbnRpdHkiO2k6MDtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czo5OiJ2YXJiaW5hcnkiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7TjtzOjExOiJzcWxzcnZfdHlwZSI7czoxNDoidmFyYmluYXJ5KG1heCkiO3M6MTI6ImRlcGVuZGVuY2llcyI7YTowOnt9fXM6ODoic2V2ZXJpdHkiO2E6MTQ6e3M6NDoibmFtZSI7czo4OiJzZXZlcml0eSI7czoxMDoibWF4X2xlbmd0aCI7aToyO3M6OToicHJlY2lzaW9uIjtpOjU7czoxNDoiY29sbGF0aW9uX25hbWUiO047czoxMToiaXNfbnVsbGFibGUiO2k6MDtzOjE0OiJpc19hbnNpX3BhZGRlZCI7aTowO3M6MTE6ImlzX2lkZW50aXR5IjtpOjA7czoxMToiaXNfY29tcHV0ZWQiO2k6MDtzOjQ6InR5cGUiO3M6ODoic21hbGxpbnQiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7czo1OiIoKDApKSI7czoxMToic3Fsc3J2X3R5cGUiO3M6ODoic21hbGxpbnQiO3M6MTI6ImRlcGVuZGVuY2llcyI7YTowOnt9czo3OiJpbmRleGVzIjthOjE6e2k6MDtzOjEyOiJzZXZlcml0eV9pZHgiO319czo0OiJsaW5rIjthOjEzOntzOjQ6Im5hbWUiO3M6NDoibGluayI7czoxMDoibWF4X2xlbmd0aCI7aTotMTtzOjk6InByZWNpc2lvbiI7aTowO3M6MTQ6ImNvbGxhdGlvbl9uYW1lIjtzOjIwOiJMYXRpbjFfR2VuZXJhbF9DSV9BSSI7czoxMToiaXNfbnVsbGFibGUiO2k6MTtzOjE0OiJpc19hbnNpX3BhZGRlZCI7aToxO3M6MTE6ImlzX2lkZW50aXR5IjtpOjA7czoxMToiaXNfY29tcHV0ZWQiO2k6MDtzOjQ6InR5cGUiO3M6ODoibnZhcmNoYXIiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7TjtzOjExOiJzcWxzcnZfdHlwZSI7czoxMzoibnZhcmNoYXIobWF4KSI7czoxMjoiZGVwZW5kZW5jaWVzIjthOjA6e319czo4OiJsb2NhdGlvbiI7YToxMzp7czo0OiJuYW1lIjtzOjg6ImxvY2F0aW9uIjtzOjEwOiJtYXhfbGVuZ3RoIjtpOi0xO3M6OToicHJlY2lzaW9uIjtpOjA7czoxNDoiY29sbGF0aW9uX25hbWUiO3M6MjA6IkxhdGluMV9HZW5lcmFsX0NJX0FJIjtzOjExOiJpc19udWxsYWJsZSI7aTowO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjE7czoxMToiaXNfaWRlbnRpdHkiO2k6MDtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czo4OiJudmFyY2hhciI7czoxMDoiZGVmaW5pdGlvbiI7TjtzOjEzOiJkZWZhdWx0X3ZhbHVlIjtOO3M6MTE6InNxbHNydl90eXBlIjtzOjEzOiJudmFyY2hhcihtYXgpIjtzOjEyOiJkZXBlbmRlbmNpZXMiO2E6MDp7fX1zOjc6InJlZmVyZXIiO2E6MTM6e3M6NDoibmFtZSI7czo3OiJyZWZlcmVyIjtzOjEwOiJtYXhfbGVuZ3RoIjtpOi0xO3M6OToicHJlY2lzaW9uIjtpOjA7czoxNDoiY29sbGF0aW9uX25hbWUiO3M6MjA6IkxhdGluMV9HZW5lcmFsX0NJX0FJIjtzOjExOiJpc19udWxsYWJsZSI7aToxO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjE7czoxMToiaXNfaWRlbnRpdHkiO2k6MDtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czo4OiJudmFyY2hhciI7czoxMDoiZGVmaW5pdGlvbiI7TjtzOjEzOiJkZWZhdWx0X3ZhbHVlIjtOO3M6MTE6InNxbHNydl90eXBlIjtzOjEzOiJudmFyY2hhcihtYXgpIjtzOjEyOiJkZXBlbmRlbmNpZXMiO2E6MDp7fX1zOjg6Imhvc3RuYW1lIjthOjEzOntzOjQ6Im5hbWUiO3M6ODoiaG9zdG5hbWUiO3M6MTA6Im1heF9sZW5ndGgiO2k6MTI4O3M6OToicHJlY2lzaW9uIjtpOjA7czoxNDoiY29sbGF0aW9uX25hbWUiO3M6MjA6IkxhdGluMV9HZW5lcmFsX0NJX0FJIjtzOjExOiJpc19udWxsYWJsZSI7aTowO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjE7czoxMToiaXNfaWRlbnRpdHkiO2k6MDtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czo3OiJ2YXJjaGFyIjtzOjEwOiJkZWZpbml0aW9uIjtOO3M6MTM6ImRlZmF1bHRfdmFsdWUiO3M6NDoiKCcnKSI7czoxMToic3Fsc3J2X3R5cGUiO3M6MTI6InZhcmNoYXIoMTI4KSI7czoxMjoiZGVwZW5kZW5jaWVzIjthOjA6e319czo5OiJ0aW1lc3RhbXAiO2E6MTM6e3M6NDoibmFtZSI7czo5OiJ0aW1lc3RhbXAiO3M6MTA6Im1heF9sZW5ndGgiO2k6NDtzOjk6InByZWNpc2lvbiI7aToxMDtzOjE0OiJjb2xsYXRpb25fbmFtZSI7TjtzOjExOiJpc19udWxsYWJsZSI7aTowO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjA7czoxMToiaXNfaWRlbnRpdHkiO2k6MDtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czozOiJpbnQiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7czo1OiIoKDApKSI7czoxMToic3Fsc3J2X3R5cGUiO3M6MzoiaW50IjtzOjEyOiJkZXBlbmRlbmNpZXMiO2E6MDp7fX19czoxMzoiY29sdW1uc19jbGVhbiI7YToxMTp7czozOiJ3aWQiO2E6MTM6e3M6NDoibmFtZSI7czozOiJ3aWQiO3M6MTA6Im1heF9sZW5ndGgiO2k6NDtzOjk6InByZWNpc2lvbiI7aToxMDtzOjE0OiJjb2xsYXRpb25fbmFtZSI7TjtzOjExOiJpc19udWxsYWJsZSI7aTowO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjA7czoxMToiaXNfaWRlbnRpdHkiO2k6MTtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czozOiJpbnQiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7TjtzOjExOiJzcWxzcnZfdHlwZSI7czozOiJpbnQiO3M6NzoiaW5kZXhlcyI7YToxOntpOjA7czoxMzoid2F0Y2hkb2dfcGtleSI7fX1zOjM6InVpZCI7YToxMzp7czo0OiJuYW1lIjtzOjM6InVpZCI7czoxMDoibWF4X2xlbmd0aCI7aTo0O3M6OToicHJlY2lzaW9uIjtpOjEwO3M6MTQ6ImNvbGxhdGlvbl9uYW1lIjtOO3M6MTE6ImlzX251bGxhYmxlIjtpOjA7czoxNDoiaXNfYW5zaV9wYWRkZWQiO2k6MDtzOjExOiJpc19pZGVudGl0eSI7aTowO3M6MTE6ImlzX2NvbXB1dGVkIjtpOjA7czo0OiJ0eXBlIjtzOjM6ImludCI7czoxMDoiZGVmaW5pdGlvbiI7TjtzOjEzOiJkZWZhdWx0X3ZhbHVlIjtzOjU6IigoMCkpIjtzOjExOiJzcWxzcnZfdHlwZSI7czozOiJpbnQiO3M6NzoiaW5kZXhlcyI7YToxOntpOjA7czo3OiJ1aWRfaWR4Ijt9fXM6NDoidHlwZSI7YToxMzp7czo0OiJuYW1lIjtzOjQ6InR5cGUiO3M6MTA6Im1heF9sZW5ndGgiO2k6NjQ7czo5OiJwcmVjaXNpb24iO2k6MDtzOjE0OiJjb2xsYXRpb25fbmFtZSI7czoyMDoiTGF0aW4xX0dlbmVyYWxfQ0lfQUkiO3M6MTE6ImlzX251bGxhYmxlIjtpOjA7czoxNDoiaXNfYW5zaV9wYWRkZWQiO2k6MTtzOjExOiJpc19pZGVudGl0eSI7aTowO3M6MTE6ImlzX2NvbXB1dGVkIjtpOjA7czo0OiJ0eXBlIjtzOjc6InZhcmNoYXIiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7czo0OiIoJycpIjtzOjExOiJzcWxzcnZfdHlwZSI7czoxMToidmFyY2hhcig2NCkiO3M6NzoiaW5kZXhlcyI7YToxOntpOjA7czo4OiJ0eXBlX2lkeCI7fX1zOjc6Im1lc3NhZ2UiO2E6MTI6e3M6NDoibmFtZSI7czo3OiJtZXNzYWdlIjtzOjEwOiJtYXhfbGVuZ3RoIjtpOi0xO3M6OToicHJlY2lzaW9uIjtpOjA7czoxNDoiY29sbGF0aW9uX25hbWUiO3M6MjA6IkxhdGluMV9HZW5lcmFsX0NJX0FJIjtzOjExOiJpc19udWxsYWJsZSI7aTowO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjE7czoxMToiaXNfaWRlbnRpdHkiO2k6MDtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czo4OiJudmFyY2hhciI7czoxMDoiZGVmaW5pdGlvbiI7TjtzOjEzOiJkZWZhdWx0X3ZhbHVlIjtOO3M6MTE6InNxbHNydl90eXBlIjtzOjEzOiJudmFyY2hhcihtYXgpIjt9czo5OiJ2YXJpYWJsZXMiO2E6MTI6e3M6NDoibmFtZSI7czo5OiJ2YXJpYWJsZXMiO3M6MTA6Im1heF9sZW5ndGgiO2k6LTE7czo5OiJwcmVjaXNpb24iO2k6MDtzOjE0OiJjb2xsYXRpb25fbmFtZSI7TjtzOjExOiJpc19udWxsYWJsZSI7aTowO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjE7czoxMToiaXNfaWRlbnRpdHkiO2k6MDtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czo5OiJ2YXJiaW5hcnkiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7TjtzOjExOiJzcWxzcnZfdHlwZSI7czoxNDoidmFyYmluYXJ5KG1heCkiO31zOjg6InNldmVyaXR5IjthOjEzOntzOjQ6Im5hbWUiO3M6ODoic2V2ZXJpdHkiO3M6MTA6Im1heF9sZW5ndGgiO2k6MjtzOjk6InByZWNpc2lvbiI7aTo1O3M6MTQ6ImNvbGxhdGlvbl9uYW1lIjtOO3M6MTE6ImlzX251bGxhYmxlIjtpOjA7czoxNDoiaXNfYW5zaV9wYWRkZWQiO2k6MDtzOjExOiJpc19pZGVudGl0eSI7aTowO3M6MTE6ImlzX2NvbXB1dGVkIjtpOjA7czo0OiJ0eXBlIjtzOjg6InNtYWxsaW50IjtzOjEwOiJkZWZpbml0aW9uIjtOO3M6MTM6ImRlZmF1bHRfdmFsdWUiO3M6NToiKCgwKSkiO3M6MTE6InNxbHNydl90eXBlIjtzOjg6InNtYWxsaW50IjtzOjc6ImluZGV4ZXMiO2E6MTp7aTowO3M6MTI6InNldmVyaXR5X2lkeCI7fX1zOjQ6ImxpbmsiO2E6MTI6e3M6NDoibmFtZSI7czo0OiJsaW5rIjtzOjEwOiJtYXhfbGVuZ3RoIjtpOi0xO3M6OToicHJlY2lzaW9uIjtpOjA7czoxNDoiY29sbGF0aW9uX25hbWUiO3M6MjA6IkxhdGluMV9HZW5lcmFsX0NJX0FJIjtzOjExOiJpc19udWxsYWJsZSI7aToxO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjE7czoxMToiaXNfaWRlbnRpdHkiO2k6MDtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czo4OiJudmFyY2hhciI7czoxMDoiZGVmaW5pdGlvbiI7TjtzOjEzOiJkZWZhdWx0X3ZhbHVlIjtOO3M6MTE6InNxbHNydl90eXBlIjtzOjEzOiJudmFyY2hhcihtYXgpIjt9czo4OiJsb2NhdGlvbiI7YToxMjp7czo0OiJuYW1lIjtzOjg6ImxvY2F0aW9uIjtzOjEwOiJtYXhfbGVuZ3RoIjtpOi0xO3M6OToicHJlY2lzaW9uIjtpOjA7czoxNDoiY29sbGF0aW9uX25hbWUiO3M6MjA6IkxhdGluMV9HZW5lcmFsX0NJX0FJIjtzOjExOiJpc19udWxsYWJsZSI7aTowO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjE7czoxMToiaXNfaWRlbnRpdHkiO2k6MDtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czo4OiJudmFyY2hhciI7czoxMDoiZGVmaW5pdGlvbiI7TjtzOjEzOiJkZWZhdWx0X3ZhbHVlIjtOO3M6MTE6InNxbHNydl90eXBlIjtzOjEzOiJudmFyY2hhcihtYXgpIjt9czo3OiJyZWZlcmVyIjthOjEyOntzOjQ6Im5hbWUiO3M6NzoicmVmZXJlciI7czoxMDoibWF4X2xlbmd0aCI7aTotMTtzOjk6InByZWNpc2lvbiI7aTowO3M6MTQ6ImNvbGxhdGlvbl9uYW1lIjtzOjIwOiJMYXRpbjFfR2VuZXJhbF9DSV9BSSI7czoxMToiaXNfbnVsbGFibGUiO2k6MTtzOjE0OiJpc19hbnNpX3BhZGRlZCI7aToxO3M6MTE6ImlzX2lkZW50aXR5IjtpOjA7czoxMToiaXNfY29tcHV0ZWQiO2k6MDtzOjQ6InR5cGUiO3M6ODoibnZhcmNoYXIiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7TjtzOjExOiJzcWxzcnZfdHlwZSI7czoxMzoibnZhcmNoYXIobWF4KSI7fXM6ODoiaG9zdG5hbWUiO2E6MTI6e3M6NDoibmFtZSI7czo4OiJob3N0bmFtZSI7czoxMDoibWF4X2xlbmd0aCI7aToxMjg7czo5OiJwcmVjaXNpb24iO2k6MDtzOjE0OiJjb2xsYXRpb25fbmFtZSI7czoyMDoiTGF0aW4xX0dlbmVyYWxfQ0lfQUkiO3M6MTE6ImlzX251bGxhYmxlIjtpOjA7czoxNDoiaXNfYW5zaV9wYWRkZWQiO2k6MTtzOjExOiJpc19pZGVudGl0eSI7aTowO3M6MTE6ImlzX2NvbXB1dGVkIjtpOjA7czo0OiJ0eXBlIjtzOjc6InZhcmNoYXIiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7czo0OiIoJycpIjtzOjExOiJzcWxzcnZfdHlwZSI7czoxMjoidmFyY2hhcigxMjgpIjt9czo5OiJ0aW1lc3RhbXAiO2E6MTI6e3M6NDoibmFtZSI7czo5OiJ0aW1lc3RhbXAiO3M6MTA6Im1heF9sZW5ndGgiO2k6NDtzOjk6InByZWNpc2lvbiI7aToxMDtzOjE0OiJjb2xsYXRpb25fbmFtZSI7TjtzOjExOiJpc19udWxsYWJsZSI7aTowO3M6MTQ6ImlzX2Fuc2lfcGFkZGVkIjtpOjA7czoxMToiaXNfaWRlbnRpdHkiO2k6MDtzOjExOiJpc19jb21wdXRlZCI7aTowO3M6NDoidHlwZSI7czozOiJpbnQiO3M6MTA6ImRlZmluaXRpb24iO047czoxMzoiZGVmYXVsdF92YWx1ZSI7czo1OiIoKDApKSI7czoxMToic3Fsc3J2X3R5cGUiO3M6MzoiaW50Ijt9fXM6NToiYmxvYnMiO2E6MTp7czo5OiJ2YXJpYWJsZXMiO2I6MTt9czo3OiJpbmRleGVzIjthOjQ6e3M6MTM6IndhdGNoZG9nX3BrZXkiO2E6MTU6e3M6MTA6ImluZGV4X25hbWUiO3M6MTM6IndhdGNoZG9nX3BrZXkiO3M6OToidHlwZV9kZXNjIjtzOjk6IkNMVVNURVJFRCI7czo5OiJpc191bmlxdWUiO2k6MTtzOjEzOiJkYXRhX3NwYWNlX2lkIjtpOjE7czoxNDoiaWdub3JlX2R1cF9rZXkiO2k6MDtzOjE0OiJpc19wcmltYXJ5X2tleSI7aToxO3M6MjA6ImlzX3VuaXF1ZV9jb25zdHJhaW50IjtpOjA7czoxMToiZmlsbF9mYWN0b3IiO2k6MDtzOjk6ImlzX3BhZGRlZCI7aTowO3M6MTE6ImlzX2Rpc2FibGVkIjtpOjA7czoxNToiaXNfaHlwb3RoZXRpY2FsIjtpOjA7czoxNToiYWxsb3dfcm93X2xvY2tzIjtpOjE7czoxNjoiYWxsb3dfcGFnZV9sb2NrcyI7aToxO3M6MTg6ImlzX2luY2x1ZGVkX2NvbHVtbiI7aTowO3M6NzoiY29sdW1ucyI7YToxOntpOjE7YTozOntzOjQ6Im5hbWUiO3M6Mzoid2lkIjtzOjE3OiJpc19kZXNjZW5kaW5nX2tleSI7aTowO3M6MTE6ImtleV9vcmRpbmFsIjtpOjE7fX19czo4OiJ0eXBlX2lkeCI7YToxNTp7czoxMDoiaW5kZXhfbmFtZSI7czo4OiJ0eXBlX2lkeCI7czo5OiJ0eXBlX2Rlc2MiO3M6MTI6Ik5PTkNMVVNURVJFRCI7czo5OiJpc191bmlxdWUiO2k6MDtzOjEzOiJkYXRhX3NwYWNlX2lkIjtpOjE7czoxNDoiaWdub3JlX2R1cF9rZXkiO2k6MDtzOjE0OiJpc19wcmltYXJ5X2tleSI7aTowO3M6MjA6ImlzX3VuaXF1ZV9jb25zdHJhaW50IjtpOjA7czoxMToiZmlsbF9mYWN0b3IiO2k6MDtzOjk6ImlzX3BhZGRlZCI7aTowO3M6MTE6ImlzX2Rpc2FibGVkIjtpOjA7czoxNToiaXNfaHlwb3RoZXRpY2FsIjtpOjA7czoxNToiYWxsb3dfcm93X2xvY2tzIjtpOjE7czoxNjoiYWxsb3dfcGFnZV9sb2NrcyI7aToxO3M6MTg6ImlzX2luY2x1ZGVkX2NvbHVtbiI7aTowO3M6NzoiY29sdW1ucyI7YToxOntpOjE7YTozOntzOjQ6Im5hbWUiO3M6NDoidHlwZSI7czoxNzoiaXNfZGVzY2VuZGluZ19rZXkiO2k6MDtzOjExOiJrZXlfb3JkaW5hbCI7aToxO319fXM6NzoidWlkX2lkeCI7YToxNTp7czoxMDoiaW5kZXhfbmFtZSI7czo3OiJ1aWRfaWR4IjtzOjk6InR5cGVfZGVzYyI7czoxMjoiTk9OQ0xVU1RFUkVEIjtzOjk6ImlzX3VuaXF1ZSI7aTowO3M6MTM6ImRhdGFfc3BhY2VfaWQiO2k6MTtzOjE0OiJpZ25vcmVfZHVwX2tleSI7aTowO3M6MTQ6ImlzX3ByaW1hcnlfa2V5IjtpOjA7czoyMDoiaXNfdW5pcXVlX2NvbnN0cmFpbnQiO2k6MDtzOjExOiJmaWxsX2ZhY3RvciI7aTowO3M6OToiaXNfcGFkZGVkIjtpOjA7czoxMToiaXNfZGlzYWJsZWQiO2k6MDtzOjE1OiJpc19oeXBvdGhldGljYWwiO2k6MDtzOjE1OiJhbGxvd19yb3dfbG9ja3MiO2k6MTtzOjE2OiJhbGxvd19wYWdlX2xvY2tzIjtpOjE7czoxODoiaXNfaW5jbHVkZWRfY29sdW1uIjtpOjA7czo3OiJjb2x1bW5zIjthOjE6e2k6MTthOjM6e3M6NDoibmFtZSI7czozOiJ1aWQiO3M6MTc6ImlzX2Rlc2NlbmRpbmdfa2V5IjtpOjA7czoxMToia2V5X29yZGluYWwiO2k6MTt9fX1zOjEyOiJzZXZlcml0eV9pZHgiO2E6MTU6e3M6MTA6ImluZGV4X25hbWUiO3M6MTI6InNldmVyaXR5X2lkeCI7czo5OiJ0eXBlX2Rlc2MiO3M6MTI6Ik5PTkNMVVNURVJFRCI7czo5OiJpc191bmlxdWUiO2k6MDtzOjEzOiJkYXRhX3NwYWNlX2lkIjtpOjE7czoxNDoiaWdub3JlX2R1cF9rZXkiO2k6MDtzOjE0OiJpc19wcmltYXJ5X2tleSI7aTowO3M6MjA6ImlzX3VuaXF1ZV9jb25zdHJhaW50IjtpOjA7czoxMToiZmlsbF9mYWN0b3IiO2k6MDtzOjk6ImlzX3BhZGRlZCI7aTowO3M6MTE6ImlzX2Rpc2FibGVkIjtpOjA7czoxNToiaXNfaHlwb3RoZXRpY2FsIjtpOjA7czoxNToiYWxsb3dfcm93X2xvY2tzIjtpOjE7czoxNjoiYWxsb3dfcGFnZV9sb2NrcyI7aToxO3M6MTg6ImlzX2luY2x1ZGVkX2NvbHVtbiI7aTowO3M6NzoiY29sdW1ucyI7YToxOntpOjE7YTozOntzOjQ6Im5hbWUiO3M6ODoic2V2ZXJpdHkiO3M6MTc6ImlzX2Rlc2NlbmRpbmdfa2V5IjtpOjA7czoxMToia2V5X29yZGluYWwiO2k6MTt9fX19czoxNzoicHJpbWFyeV9rZXlfaW5kZXgiO3M6MTM6IndhdGNoZG9nX3BrZXkiO30=';

    $values = unserialize(base64_decode($values_encoded));
    $columnInformation = unserialize(base64_decode($columninformation_encoded));

    /** @var MyStatement */
    $st = $cnn->prepare($query, $pdo_options);

    $st->bindValues($values, ':db_insert', $columnInformation);
    $st->execute();

    $st = $cnn->query("SELECT * FROM [$tbname]");
    var_dump($st->fetchAll(PDO::FETCH_ASSOC));

    dropTable($cnn, $tbname);
    unset($st);
    unset($cnn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
array(1) {
  [0]=>
  array(11) {
    ["wid"]=>
    string(1) "1"
    ["uid"]=>
    string(1) "0"
    ["type"]=>
    string(3) "php"
    ["message"]=>
    string(51) "%type: @message in %function (line %line of %file)."
    ["variables"]=>
    string(2188) "a:5:{s:5:"%type";s:45:"Drupal\Core\Database\DatabaseExceptionWrapper";s:8:"@message";s:1913:"SQLSTATE[IMSSP]: An error occurred translating the query string to UTF-16: No mapping for the Unicode character exists in the target multi-byte code page.
.: MERGE INTO [cache_data] _target
USING (SELECT T.* FROM (values(:db_insert_placeholder_0, :db_insert_placeholder_1, :db_insert_placeholder_2, :db_insert_placeholder_3, :db_insert_placeholder_4, :db_insert_placeholder_5, :db_insert_placeholder_6)) as T([cid], [expire], [created], [tags], [checksum], [data], [serialized])) _source
ON _target.[cid] = _source.[cid]
WHEN MATCHED THEN UPDATE SET _target.[expire] = _source.[expire], _target.[created] = _source.[created], _target.[tags] = _source.[tags], _target.[checksum] = _source.[checksum], _target.[data] = _source.[data], _target.[serialized] = _source.[serialized]
WHEN NOT MATCHED THEN INSERT ([cid], [expire], [created], [tags], [checksum], [data], [serialized]) VALUES (_source.[cid], _source.[expire], _source.[created], _source.[tags], _source.[checksum], _source.[data], _source.[serialized])
OUTPUT $action;; Array
(
    [:db_insert_placeholder_0] => Array
        (
            [value] => route:/:XDEBUG_SESSION_START=58E1C1C4
            [datatype] => 2
        )

    [:db_insert_placeholder_1] => Array
        (
            [value] => -1
            [datatype] => 2
        )

    [:db_insert_placeholder_2] => Array
        (
            [value] => 1470205773.7
            [datatype] => 2
        )

    [:db_insert_placeholder_3] => Array
        (
            [value] => route_match
            [datatype] => 2
        )

    [:db_insert_placeholder_4] => Array
        (
            [value] => 4
            [datatype] => 2
        )

    [:db_insert_placeholder_5] => Array
        (
            [value] => Resource id #4
            [datatype] => 3
        )

    [:db_insert_placeholder_6] => Array
        (
            [value] => 1
            [datatype] => 2
        )

)
";s:9:"%function";s:65:"Drupal\Core\Routing\RouteProvider->getRouteCollectionForRequest()";s:5:"%file";s:52:"D:\d8\core\lib\Drupal\Core\Routing\RouteProvider.php";s:5:"%line";i:167;}"
    ["severity"]=>
    string(1) "3"
    ["link"]=>
    string(0) ""
    ["location"]=>
    string(64) "http://local.d7test.com/index.php/?XDEBUG_SESSION_START=58E1C1C4"
    ["referer"]=>
    string(0) ""
    ["hostname"]=>
    string(9) "127.0.0.1"
    ["timestamp"]=>
    string(10) "1470205774"
  }
}
