It is very easy to override a component of the library.

In the following example, we want to extend the native pipeline object in order to add a progress bar.

First, let's create the new pipeline class:

```php
namespace MyCompany\MyProject\ETL;

final class ProgressAwarePipeline extends Pipeline
{
    /** @var \Symfony\Component\Console\Helper\ProgressBar */
    private $progressBar;

    /**
     * @param  \Symfony\Component\Console\Helper\ProgressBar  $progressBar
     */
    public function __construct(ProgressBar $progressBar)
    {
        $this->progressBar = $progressBar;
    }

    public function rewind(): void
    {
        $this->progressBar->setProgress(0);

        parent::rewind();
    }

    public function next(): void
    {
        parent::next();

        $this->progressBar->setProgress($this->key);
    }

    protected function finalize(): void
    {
        parent::finalize();

        $this->progressBar->finish();
    }
}
```

In a second step, you need to set that all these objects be available with DI (if you use DI). The below example is with Symfony.

```yaml
# services.yaml
services:
  
  Symfony\Component\Console\Helper\ProgressBar:
    shared: false

  Wizaplace\Etl\Etl:
    shared: false
    arguments:
      $progressBar: 'MyCompany\MyProject\ETL\ProgressAwarePipeline'
```