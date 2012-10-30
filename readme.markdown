CModel 1.0
==========

    **ALERT**  Although this is usable, the demo and documentation are very rough! Hopefully this will improve soon :)

CModel is a model template for Zend Framework. Included are:

* The CModel library (inside the `/library/CModel` directory)
* A sample project
* A script for creating the MySQL database used in the sample project (`/data/cmodel.sql`)
* Utility functions for creating your model from existing tables

Requirements
------------

* PHP 5.3+ (this may work in lower versions, but I'm too lazy to test)
* MySQL
* Apache
* Zend Framework 1.10.3+ (again, I'm sure you can use older versions, but this is the one I used to create the sample project, and I'm lazy)

History
-------

[Pricetag](http://pricetaghq.com) is a project created by a small team of talented people. I was in charge of programming, and at first I had complete control over how things would work internally. So, after deciding to use Zend Framework as a starting point, the next logical step was building the model. I quickly realized Zend Framework doesn't provide any classes for the Model part of the MVC architecture (despite its excellent support for the other 2 thirds of the equation), so I had to build my own from scratch. I found some advice on the internet, and this thing started to evolve on its own from some basic concepts like lazy loading of related objects.

Some months later, Pricetag is pretty much stable (I, of course, attribute it to the project's excellent foundations, that is, the model), so I wanted to share what I did with the world.

It should be noted that Pricetag was the first time I'd ever worked with this framework, and its documentation has been a bit unhelpful, so I tried to learn the best I could, trial and error, and a lot of time spent looking at its source code. It's been fun, but I'm sure there could be things here that can be done in a much better way.

So, what I'm doing is copying Pricetag's model's base classes and dev tools (a couple of actions in a controller that only gets used in a development environment), and trying to put as much as I can in a separate library. Pricetag's code has everything in one place, but the idea is eventually to use these files as a separate library.

Use
---

(instructions on how to use the sample project, and how to import the library to an existing project or create one from scratch)

To-do
-----

* Build a simple but useful sample application to show off the model
* Write unit tests for everything