<h1>My page</h1>

<ul>
    <li><a href="<?=$link->to('index', 'index')?>">Index Page</a></li>
    <li><a href="<?=$link->to('index', 'other')?>">Other Page</a></li>
</ul>

<?=$content->output()?>
