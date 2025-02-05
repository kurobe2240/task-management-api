using AutoMapper;
using TaskManagement.API.Models;
using TaskManagement.API.DTOs;

namespace TaskManagement.API.Profiles
{
    public class TaskItemProfile : Profile
    {
        public TaskItemProfile()
        {
            CreateMap<TaskItem, TaskItemDto>().ReverseMap();
        }
    }
} 