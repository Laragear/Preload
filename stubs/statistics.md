# Preloader Statistics

**Generated at**: @generated_at
**Loading method**: @mechanism

| Statistic         | Value                     |
|-------------------|---------------------------|
| List Memory Limit | @preloader_memory_limit   |
| Files excluded    | @preloader_excluded       |
| Files included    | @preloader_included       |
| -                 | -                         |
| Used Memory       | @opcache_memory_used MB   |
| Free Memory       | @opcache_memory_free MB   |
| Wasted Memory     | @opcache_memory_wasted MB |
| Cached files      | @opcache_files            |
| Hit rate          | @opcache_hit_rate%        |
| Misses            | @opcache_misses           |

# Information

This file is generated automatically by the [Laragear Preloader](https://github.com/laragear/preload) library.

The script `preload.php` fetches each file from the list inside `list.txt` file
into Opcache. To full enable preloading the list of files, add the script into
your `php.ini` as the value of the `opcache.preload` key as it's shown below:

    opcache.preload=@output

For more information, [Laragear Preloader](https://github.com/laragear/preload).

