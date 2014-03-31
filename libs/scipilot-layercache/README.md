# Scipilot / Layercache

Layercache is a flexible multi-layer key-value cache designed to run on a load-balanced web server platform in PHP.

The scenario it solves is thus: 

- You have data provider classes/functions which are slow and inefficient, or legacy and complicated. 
- You can't (be bothered to) optimise them.
- Luckily the data they output doesn't change very often so you can cache their output.
- You're serving this data in a typical load-balanced web platform with many web-servers perhaps pulling from a shared SAN and/or database.
 - You don't have memcached or other fancy features, perhaps because you're on a crappy shared host. But we can implement it in PHP.

We have several places data could possibly be cached in this scenario:

1. in private memory. This is accessible to each http process during the lifecycle of the page load, on one web server. Useful for e.g. config settings, really common queries, ORM-sublayers.
2. in shared memory. This is accessible to all http processes concurrently on a server, during the lifecycle of the page load.
3. in local filesystem. This is the same as 2) but more likely to be possible (all web servers have a tmp folder right?)
4. in a shared filesystem. Some web farms have a SAN or NFS mount which they can all access. Sure it's a bottleneck and single point of failure, but it works. This is accessible by all web servers and all http processes, concurrently.
5. in a shared database. Most websites have access to a DB, so this is an obvious place to store cache data. Accessible to all servers and processess, concurrently.

On read, the cache checks the top layer first for the key you request. If it's not in the top layer it traverses down through to the ever slower layers until it finds the data. When it finds the data, it writes it back up into the higher layers on return. That way, the lower shared layers provide a source for data to other servers. The behaviour on cache miss depends on the overall architecture (see below).

On write, the cache writes-through to all layers. Of course on a load-balanced web farm, this means that the upper layers will only be populated on the local processes or servers. The lower shared layers will of course be populated too. The write-through process is, by definition synchronous and blocking, so an initial cache write is a performance hit.

So what happends if the data isn't found in the cache on read? 
Well that depends on how your application has constructed the layers. 

Luckily the system is flexible and provides you with TWO quite different approaches.

Level 6) (or whatever the bottom layer is) could be the data provider itself. This way, the client-code always asks the cache for data regardless of where it comes from. 
The beauty of the layercache model is that you can implement the lowest layer as the actual (slow, legacy) data generator. 
Therefore data it generates will get automatically written-up into the cache as part of the write-up model. 
You simply have to implement a LayerCache class and insert it into the bottommost layer of the Stack. 
Obviously your LayerCache data-provider class will ignore writes!
A possibly drawback of this, is you may lose the ability to scope the generation of data, as the data-provider is deep below
the stack and out of scope of your top-level processes. There's many potential solutions to this, including closures, callbacks
or even globals. It is PHP after all.

The alternative way of approching it, is to have the data-provider above the cache. 
The client code asks the provider for the data, and it can manage the cache internally in a write-back fashion.
This is really useful if you want to hide the caching from the application code, 
and keep it inside your slow provider magically making it faster. 
This is good for legacy code bases where you want to limit the impact of adding caching points. The calling code need never know.
Of course you will need to implement the simple "generate on miss" algorithm in the provider, 
which checks the cache for its own data and re-generates the data if none is cached (or has expired). 
This is illustrated by the data provider class examples.

See HelloWorldProvider class for example of an app using the cache.  

The two main components of the cache architecture are:

1. The Layer Stack is the entry point, or manager, of the cache. This class holds the layers and operates as a unified cache. It's the only thing the app needs to interact with, once everything is set up.
1. The Cache Layer - there are a few provided (Memory, File, DB), or you can write your own.

The Layer Stack is a singleton used by an application as the entry point to the cache.
It contains as many cache layers as required and writes to them for you via a simple unified interface: just read and write.

Usage:

1. on app init, you create your cache layers, register them with the stack
2. then you write into the cache. 
3.  then you read from the cache.
  
The Cache Layer writes and reads data under the Layer Cache Stack model. e.g. memory layer, file layer, DB layer.
The app creates these layers, and registeres them into the stack before use.
After they're in the stack, you probably won't touch them again.
  
##CONCURRENCY RULES

The CacheLayer implementations must perform normally when multiple processes are writing and reading. 
These processes could be on the same host, or different hosts for shared/distributed layers.

1. two or more processes should be allowed to (try to) write to the same key simultaneously, without corrupting the data, 
i.e. one of them will always come second and overwrite the other's data. 
2. two or more processes must be able to read while a write is happening without corrupted data. 
i.e. the read process will either get the first value intact, or the second value intact.
3. two or more processes should be able to read from the same key simultaneously, 
i.e. it would be preferable that the access was completely parallel, not locked, for speed reasons.
  
Concurrency management will be acheived via semaphore, locking, queueing, or buffering etc. as required.
Without application-level atomic-set-and-test, the cache values could therefore appear to change between write and read.
  
##Testing
The library is supplied with unit tests which should cover all basic functions and one scenario of layers.
Simply execute tests/run.sh and it will use the supplied PHPUnit.phar to run the tests. Feel free to replace this
with your own PHPUnit. 

At the time of writing there are 65 tests and 797 assertions passing. Most of these assertions are due to loops, 
probably a lot of them in the concurrency tests, so it sounds like more than it really is.

If you implement a LayerCache you should copy one of the existing concrete layer test classes, and point it at your implementation.
Most of the work will be done by the Layer Interface Test class (all the tests just call super), 
making testing really, really simple - you have no excuse! The parent test class also counts the tests to make sure you've called them all. 
How future-proofed is that!

## Bundled classes

I've tried to keep this library as self-contained as possible, but a couple of classes have crept in from my wider library (yet to be released).

The Result class is a general DTO I use for returning data and error handling. 
There's a logger I sometimes use for debugging. 
And there's one of my of many compatibility wrappers for when a crappy server doesn't have libraries installed.

## Licence

This library is licenced under the LGPL v3.

Simply put: you can use this software pretty much as you wish as long as you retain this licence, make the source available and don't blame me for anything. 
I'd also really like to see any changes / fixes / suggestions - thanks!
