using System;
using TaskManagement.API.Models;

namespace TaskManagement.API.DTOs
{
    public class ProjectParameters
    {
        private const int MaxPageSize = 50;
        private int _pageSize = 10;

        // ページネーション
        public int PageNumber { get; set; } = 1;
        public int PageSize
        {
            get => _pageSize;
            set => _pageSize = value > MaxPageSize ? MaxPageSize : value;
        }

        // 検索
        public string? SearchTerm { get; set; }

        // フィルタリング
        public ProjectStatus? Status { get; set; }
        public DateTime? StartDateFrom { get; set; }
        public DateTime? StartDateTo { get; set; }
        public DateTime? EndDateFrom { get; set; }
        public DateTime? EndDateTo { get; set; }

        // ソート
        public string? OrderBy { get; set; } // CreatedAt, StartDate, TaskCount
        public bool IsDescending { get; set; } = false;
    }

    public class PagedResponse<T>
    {
        public IEnumerable<T> Items { get; set; } = new List<T>();
        public int CurrentPage { get; set; }
        public int TotalPages { get; set; }
        public int PageSize { get; set; }
        public int TotalCount { get; set; }
        public bool HasPrevious => CurrentPage > 1;
        public bool HasNext => CurrentPage < TotalPages;
    }
} 