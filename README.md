=== You can make your software 2 time faster.

Problems:
1. How to take parameter or service instance in Controller? In Command? In Service? In Twig Helper? In Event Listener?
2. Sending Email is slow, How to do it faster?

Answers:

1. Everywhere simmilar
```
$this->depedencies->getParameter('param_name');
```

2. 
```
$this->depedencies->sendEmail('email@example.com', 'template_name', array('foo_template_parameter' => 'bar'));
```

