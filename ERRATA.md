## Errata (Design Decisions)

### Reasoning Behind the Order of Preferred Random Data Sources

The order is:

 1. `fread() /dev/urandom if available`
 2. `mcrypt_create_iv($bytes, MCRYPT_CREATE_IV)`
 3. `COM('CAPICOM.Utilities.1')->GetRandom()`
 4. `openssl_random_pseudo_bytes()`

We read `/dev/urandom` first (if it exists).
This is the preferred file to read for random data for cryptographic
purposes for BSD and Linux.

Despite [strongly urging people not to use mcrypt in their projects](https://paragonie.com/blog/2015/05/if-you-re-typing-word-mcrypt-into-your-code-you-re-doing-it-wrong),
because libmcrypt is abandonware and the API puts too much responsibility on the
implementor, we prioritize `mcrypt_create_iv()` with `MCRYPT_DEV_URANDOM` above
the remaining implementations.

The reason is simple: `mcrypt_create_iv()` is part of PHP's `ext/mcrypt` code,
and is not part `libmcrypt`. It actually does the right thing:

 * On Unix-based operating systems, it reads from `/dev/urandom`, which is the
   sane and correct thing to do.
 * On Windows, it reads from `CryptGenRandom`, which is an exclusively Windows
   way to get random bytes.

If we're on Windows and don't have access to `mcrypt`, we use `CAPICOM.Utilities.1`.

Finally, we use `openssl_random_pseudo_bytes()` **as a last resort**. Internally,
this function calls `RAND_pseudo_bytes()`, which has been [deprecated](https://github.com/paragonie/random_compat/issues/5)
by the OpenSSL team. Furthermore, [it might silently return weak random data](https://github.com/paragonie/random_compat/issues/6#issuecomment-119564973)
if it is called before OpenSSL's **userspace** CSPRNG is seeded. Also, 
[you want the OS CSPRNG, not a userspace CSPRNG](http://sockpuppet.org/blog/2014/02/25/safely-generate-random-numbers/).
