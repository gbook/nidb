# Editing the config file

System-wide settings are stored in the config file. The default location is `/nidb/nidb.cfg`.

The NiDB Settings page allows you to edit the configuration file directly. When the page is saved, the config file is updated. But the config file can be edited manually, which is useful when the website is unavailable or you need to edit settings through the command line. To edit the file by hand, start vim from a terminal. (if `vim` is not be installed on your system, run `sudo yum install vim`)

```bash
vim /nidb/nidb.cfg
```

This will start vim in the terminal. Within vim:

1. Use the arrow keys to navigate to the variable you want to edit
2. Press the `[insert]` key
3. Edit as normal
4. When done editing, press `[esc]` key
5. Type `:wq` which will save the file and quit `vim`

### Special Config Variables

Some variables can only be changed by editing the config file directly and cannot be changed from the NiDB settings page.

`offline` - Set to `1` if the website should be unavailable to users, `0` for normal access. Default is `0`

`debug` - Set to `1` if the website should print out every SQL statement, and other debug information. Default is `0`

`hideerrors` - Set to `1` if the website should hide SQL errors from the user. 0 otherwise. Default is `0`
