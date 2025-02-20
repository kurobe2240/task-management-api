using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using FluentAssertions;
using Microsoft.Extensions.Logging;
using Microsoft.Extensions.Options;
using Moq;
using Nest;
using TaskManagement.API.DTOs;
using TaskManagement.API.Models;
using TaskManagement.API.Services;
using TaskManagement.API.Settings;
using Xunit;

namespace TaskManagement.Tests.Unit.Services
{
    public class SearchServiceTests
    {
        private readonly Mock<IElasticClient> _mockElasticClient;
        private readonly Mock<ILogger<SearchService>> _mockLogger;
        private readonly Mock<IOptions<ElasticSearchSettings>> _mockOptions;
        private readonly SearchService _searchService;

        public SearchServiceTests()
        {
            _mockElasticClient = new Mock<IElasticClient>();
            _mockLogger = new Mock<ILogger<SearchService>>();
            _mockOptions = new Mock<IOptions<ElasticSearchSettings>>();

            _mockOptions.Setup(opt => opt.Value)
                .Returns(new ElasticSearchSettings
                {
                    Url = "http://localhost:9200",
                    DefaultIndex = "tasks"
                });

            _searchService = new SearchService(
                _mockElasticClient.Object,
                _mockOptions.Object,
                _mockLogger.Object);
        }

        [Fact]
        public async Task SearchTasksAsync_ReturnsSearchResult()
        {
            // Arrange
            var searchParams = new SearchParameters
            {
                SearchTerm = "test",
                PageNumber = 1,
                PageSize = 10,
                SearchField = "title",
                MatchExactPhrase = false
            };

            var taskItems = new List<TaskItem>
            {
                new TaskItem { Id = 1, Title = "Test Task 1" },
                new TaskItem { Id = 2, Title = "Test Task 2" }
            };

            var hits = taskItems.Select(t => new Mock<IHit<TaskItem>>()).ToList();
            for (int i = 0; i < hits.Count; i++)
            {
                hits[i].Setup(h => h.Source).Returns(taskItems[i]);
            }

            var searchResponse = new Mock<ISearchResponse<TaskItem>>();
            searchResponse.Setup(r => r.IsValid).Returns(true);
            searchResponse.Setup(r => r.Total).Returns(2);
            searchResponse.Setup(r => r.Took).Returns(100);
            searchResponse.Setup(r => r.Hits).Returns(hits.Select(h => h.Object).ToList().AsReadOnly());
            searchResponse.Setup(r => r.Aggregations).Returns((AggregateDictionary)null);

            _mockElasticClient.Setup(client => client.SearchAsync<TaskItem>(
                It.IsAny<Func<SearchDescriptor<TaskItem>, ISearchRequest>>(),
                default))
                .Callback<Func<SearchDescriptor<TaskItem>, ISearchRequest>, object>((f, _) =>
                {
                    var descriptor = new SearchDescriptor<TaskItem>();
                    var request = f(descriptor);
                    request.Should().NotBeNull();
                })
                .ReturnsAsync(searchResponse.Object);

            // Act
            var result = await _searchService.SearchTasksAsync(searchParams);

            // Assert
            result.Should().NotBeNull();
            result.TotalCount.Should().Be(2);
            result.Items.Should().HaveCount(2);
            result.PageNumber.Should().Be(searchParams.PageNumber);
            result.PageSize.Should().Be(searchParams.PageSize);
            result.Facets.Should().NotBeNull();
        }

        [Fact]
        public async Task SearchTasksAsync_WithInvalidResponse_ThrowsException()
        {
            // Arrange
            var searchParams = new SearchParameters
            {
                SearchTerm = "test",
                PageNumber = 1,
                PageSize = 10,
                SearchField = "title",
                MatchExactPhrase = false
            };

            var searchResponse = new Mock<ISearchResponse<TaskItem>>();
            searchResponse.Setup(r => r.IsValid).Returns(false);
            searchResponse.Setup(r => r.DebugInformation).Returns("Error occurred");
            searchResponse.Setup(r => r.Hits).Returns(new List<IHit<TaskItem>>().AsReadOnly());
            searchResponse.Setup(r => r.Aggregations).Returns((AggregateDictionary)null);

            _mockElasticClient.Setup(client => client.SearchAsync<TaskItem>(
                It.IsAny<Func<SearchDescriptor<TaskItem>, ISearchRequest>>(),
                default))
                .Callback<Func<SearchDescriptor<TaskItem>, ISearchRequest>, object>((f, _) =>
                {
                    var descriptor = new SearchDescriptor<TaskItem>();
                    var request = f(descriptor);
                    request.Should().NotBeNull();
                })
                .ReturnsAsync(searchResponse.Object);

            // Act & Assert
            var exception = await Assert.ThrowsAsync<Exception>(() => _searchService.SearchTasksAsync(searchParams));
            exception.Message.Should().Be("検索の実行中にエラーが発生しました。");
        }

        [Fact]
        public async Task SearchProjectsAsync_ReturnsSearchResult()
        {
            // Arrange
            var searchParams = new SearchParameters
            {
                SearchTerm = "test project",
                PageNumber = 1,
                PageSize = 10,
                SearchField = "name",
                MatchExactPhrase = false
            };

            var projects = new List<Project>
            {
                new Project { Id = 1, Name = "Test Project 1" },
                new Project { Id = 2, Name = "Test Project 2" }
            };

            var hits = projects.Select(p => new Mock<IHit<Project>>()).ToList();
            for (int i = 0; i < hits.Count; i++)
            {
                hits[i].Setup(h => h.Source).Returns(projects[i]);
            }

            var searchResponse = new Mock<ISearchResponse<Project>>();
            searchResponse.Setup(r => r.IsValid).Returns(true);
            searchResponse.Setup(r => r.Total).Returns(2);
            searchResponse.Setup(r => r.Took).Returns(100);
            searchResponse.Setup(r => r.Hits).Returns(hits.Select(h => h.Object).ToList().AsReadOnly());
            searchResponse.Setup(r => r.Aggregations).Returns((AggregateDictionary)null);

            _mockElasticClient.Setup(client => client.SearchAsync<Project>(
                It.IsAny<Func<SearchDescriptor<Project>, ISearchRequest>>(),
                default))
                .Callback<Func<SearchDescriptor<Project>, ISearchRequest>, object>((f, _) =>
                {
                    var descriptor = new SearchDescriptor<Project>();
                    var request = f(descriptor);
                    request.Should().NotBeNull();
                })
                .ReturnsAsync(searchResponse.Object);

            // Act
            var result = await _searchService.SearchProjectsAsync(searchParams);

            // Assert
            result.Should().NotBeNull();
            result.TotalCount.Should().Be(2);
            result.Items.Should().HaveCount(2);
            result.PageNumber.Should().Be(searchParams.PageNumber);
            result.PageSize.Should().Be(searchParams.PageSize);
            result.Facets.Should().NotBeNull();
        }

        [Fact]
        public async Task IndexTaskAsync_SuccessfullyIndexesTask()
        {
            // Arrange
            var task = new TaskItem
            {
                Id = 1,
                Title = "Test Task",
                Description = "Test Description"
            };

            var indexResponse = new Mock<IndexResponse>();
            indexResponse.Setup(r => r.IsValid).Returns(true);

            _mockElasticClient.Setup(client => client.IndexDocumentAsync(task, default))
                .ReturnsAsync(indexResponse.Object);

            // Act & Assert
            await _searchService.IndexTaskAsync(task);
            _mockElasticClient.Verify(client => client.IndexDocumentAsync(task, default), Times.Once);
        }

        [Fact]
        public async Task DeleteTaskFromIndexAsync_SuccessfullyDeletesTask()
        {
            // Arrange
            var taskId = 1;
            var deleteResponse = new Mock<DeleteResponse>();
            deleteResponse.Setup(r => r.IsValid).Returns(true);

            _mockElasticClient.Setup(client => client.DeleteAsync<TaskItem>(taskId, null, default))
                .ReturnsAsync(deleteResponse.Object);

            // Act & Assert
            await _searchService.DeleteTaskFromIndexAsync(taskId);
            _mockElasticClient.Verify(client => client.DeleteAsync<TaskItem>(taskId, null, default), Times.Once);
        }
    }
} 