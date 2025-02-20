using System;
using System.Threading.Tasks;
using Microsoft.Extensions.Caching.Memory;
using Microsoft.Extensions.Configuration;
using System.Collections.Concurrent;
using Microsoft.Extensions.Logging;

namespace TaskManagement.API.Services
{
    public interface ICacheService
    {
        Task<T?> GetAsync<T>(string key);
        Task SetAsync<T>(string key, T value);
        Task SetAsync<T>(string key, T value, TimeSpan expirationTime);
        Task RemoveAsync(string key);
    }

    public class CacheService : ICacheService
    {
        private readonly IMemoryCache _cache;
        private readonly IConfiguration _configuration;
        private readonly ConcurrentDictionary<string, SemaphoreSlim> _locks = new();
        private readonly TimeSpan _defaultExpiration;
        private readonly ILogger<CacheService> _logger;

        public CacheService(IMemoryCache cache, IConfiguration configuration, ILogger<CacheService> logger)
        {
            _cache = cache;
            _configuration = configuration;
            _defaultExpiration = TimeSpan.FromMinutes(30); // デフォルトの有効期限を30分に設定
            _logger = logger;
        }

        public async Task<T?> GetAsync<T>(string key)
        {
            try
            {
                return await Task.FromResult(_cache.Get<T>(key));
            }
            catch (Exception ex)
            {
                // キャッシュの取得に失敗した場合はnullを返す
                _logger.LogError(ex, "キャッシュの取得中にエラーが発生しました。 Key: {Key}", key);
                return default;
            }
        }

        public async Task SetAsync<T>(string key, T value)
        {
            await SetAsync(key, value, _defaultExpiration);
        }

        public async Task SetAsync<T>(string key, T value, TimeSpan expirationTime)
        {
            var lockObj = _locks.GetOrAdd(key, k => new SemaphoreSlim(1, 1));

            try
            {
                await lockObj.WaitAsync();

                var cacheEntryOptions = new MemoryCacheEntryOptions()
                    .SetSlidingExpiration(expirationTime)
                    .RegisterPostEvictionCallback((key, value, reason, state) =>
                    {
                        _logger.LogDebug("キャッシュエントリが削除されました。 Key: {Key}, Reason: {Reason}", key, reason);
                    });

                _cache.Set(key, value, cacheEntryOptions);
                await Task.CompletedTask;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "キャッシュの設定中にエラーが発生しました。 Key: {Key}", key);
                throw;
            }
            finally
            {
                lockObj.Release();
            }
        }

        public async Task RemoveAsync(string key)
        {
            var lockObj = _locks.GetOrAdd(key, k => new SemaphoreSlim(1, 1));

            try
            {
                await lockObj.WaitAsync();
                _cache.Remove(key);
                await Task.CompletedTask;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "キャッシュの削除中にエラーが発生しました。 Key: {Key}", key);
                throw;
            }
            finally
            {
                lockObj.Release();
            }
        }
    }
} 